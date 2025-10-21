<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WpSites;
use Illuminate\Support\Facades\Log;

class RefreshWordpressTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wordpress:refresh-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh JWT tokens for WordPress sites with auto_refresh enabled , runs every 12 hours';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting WordPress token refresh job...');

        $sites = WpSites::where('auto_refresh', true)
            ->where('status', 'active')
            ->get();

        if ($sites->isEmpty()) {
            $this->info('No sites found with auto_refresh enabled.');
            return 0;
        }

        $this->info("Found {$sites->count()} site(s) to check...");

        $refreshed = 0;
        $validated = 0;
        $failed = 0;

        foreach ($sites as $site) {
            try {
                $shouldRefresh = false;

                if (!$site->jwt_expires_at) {
                    $shouldRefresh = true;
                    $this->info("Site {$site->site_name} (ID: {$site->id}): No expiry time, refreshing...");
                } else {
                    $expiresAt = \Carbon\Carbon::parse($site->jwt_expires_at);
                    $hoursSinceIssued = now()->diffInHours($expiresAt->copy()->subDays(2), false);

                    if ($hoursSinceIssued >= 12) {
                        $shouldRefresh = true;
                        $this->info("Site {$site->site_name} (ID: {$site->id}): Token is {$hoursSinceIssued} hours old, refreshing...");
                    }
                }

                if ($shouldRefresh) {
                    $result = $this->refreshToken($site);
                    if ($result) {
                        $refreshed++;
                        $this->info("✓ Site {$site->site_name} (ID: {$site->id}): Token refreshed successfully");
                    } else {
                        $failed++;
                        $this->error("✗ Site {$site->site_name} (ID: {$site->id}): Failed to refresh token");
                    }
                } else {
                    $result = $this->validateToken($site);
                    if ($result) {
                        $validated++;
                        $this->info("✓ Site {$site->site_name} (ID: {$site->id}): Token is still valid");
                    } else {
                        $this->warn("! Site {$site->site_name} (ID: {$site->id}): Token validation failed, attempting refresh...");
                        $result = $this->refreshToken($site);
                        if ($result) {
                            $refreshed++;
                            $this->info("✓ Site {$site->site_name} (ID: {$site->id}): Token refreshed successfully");
                        } else {
                            $failed++;
                            $this->error("✗ Site {$site->site_name} (ID: {$site->id}): Failed to refresh token");
                        }
                    }
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ Site {$site->site_name} (ID: {$site->id}): Exception - {$e->getMessage()}");
                Log::error("WordPress token refresh failed for site {$site->id}: {$e->getMessage()}");
            }
        }

        $this->info("\nToken Refresh Summary:");
        $this->info("- Validated: {$validated}");
        $this->info("- Refreshed: {$refreshed}");
        $this->info("- Failed: {$failed}");

        Log::info("WordPress token refresh completed. Validated: {$validated}, Refreshed: {$refreshed}, Failed: {$failed}");

        return 0;
    }

    /**
     * Validate existing JWT token
     */
    private function validateToken(WpSites $site)
    {
        try {
            $validateEndpoint = $site->domain . "/wp-json/jwt-auth/v1/token/validate";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $validateEndpoint);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $site->jwt_token,
                'Content-Type: application/json'
            ]);

            $validateResponse = curl_exec($ch);
            $validateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($validateResponse === false) {
                return false;
            }

            $validateResult = json_decode($validateResponse, true);

            if ($validateHttpCode === 200 && isset($validateResult['code']) && $validateResult['code'] === 'jwt_auth_valid_token') {
                $site->last_connected_at = now();
                $site->save();
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Token validation error for site {$site->id}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get a new JWT token for the site
     */
    private function refreshToken(WpSites $site)
    {
        try {
            $tokenEndpoint = $site->domain . "/wp-json/jwt-auth/v1/token";
            $params = [
                'username' => $site->username,
                'password' => $site->password
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenEndpoint);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false || !empty($curlError)) {
                Log::error("WordPress connection failed for site {$site->id}: " . $curlError);
                $site->status = 'inactive';
                $site->connection_error = 'Connection failed: ' . $curlError;
                $site->save();
                return false;
            }

            $tokenResponse = json_decode($response, true);

            if ($httpCode !== 200 || !isset($tokenResponse['token'])) {
                $errorMessage = isset($tokenResponse['message']) ? $tokenResponse['message'] : 'Invalid credentials';
                Log::error("WordPress token failed for site {$site->id}: " . $errorMessage);
                $site->status = 'inactive';
                $site->connection_error = $errorMessage;
                $site->save();
                return false;
            }

            $site->jwt_token = $tokenResponse['token'];
            $site->jwt_expires_at = now()->addHours(6);
            $site->status = 'active';
            $site->last_connected_at = now();
            $site->connection_error = null;
            $site->save();

            return true;
        } catch (\Exception $e) {
            Log::error("Token refresh error for site {$site->id}: {$e->getMessage()}");
            return false;
        }
    }
}
