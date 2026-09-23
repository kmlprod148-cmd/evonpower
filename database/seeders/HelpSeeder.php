<?php

namespace Database\Seeders;

use App\Models\HelpCategory;
use App\Models\HelpArticle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HelpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating help categories and articles...');

        // Create categories
        $categories = [
            [
                'name' => 'Getting Started',
                'slug' => 'getting-started',
                'description' => 'Learn how to get started with EVON charging stations',
                'icon' => '🚀',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'FAQ',
                'slug' => 'faq',
                'description' => 'Frequently Asked Questions about EVON services',
                'icon' => '❓',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Troubleshooting',
                'slug' => 'troubleshooting',
                'description' => 'Common issues and how to resolve them',
                'icon' => '🔧',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Billing & Payments',
                'slug' => 'billing-payments',
                'description' => 'Information about billing and payment methods',
                'icon' => '💳',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Account Management',
                'slug' => 'account-management',
                'description' => 'Manage your account settings and preferences',
                'icon' => '👤',
                'order' => 5,
                'is_active' => true,
            ],
        ];

        $categoryIds = [];
        foreach ($categories as $catData) {
            $category = HelpCategory::updateOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );
            $categoryIds[$catData['slug']] = $category->id;
        }

        // Create articles for Getting Started
        $gettingStartedArticles = [
            [
                'title' => 'How to Create an Account',
                'slug' => 'how-to-create-account',
                'content' => '<h2>Creating Your EVON Account</h2><p>Follow these steps to create your account:</p><ol><li>Visit the EVON homepage</li><li>Click on "Sign Up" or "Register"</li><li>Enter your email address and create a password</li><li>Verify your email address</li><li>Complete your profile</li></ol><p>Once your account is created, you can start using EVON charging stations immediately.</p>',
                'excerpt' => 'Learn how to create your EVON account in just a few simple steps.',
                'meta_title' => 'How to Create an Account | EVON Help',
                'meta_description' => 'Step-by-step guide to creating your EVON account and getting started with electric vehicle charging.',
                'is_published' => true,
                'is_featured' => true,
                'order' => 1,
                'tags' => json_encode(['account', 'registration', 'signup']),
            ],
            [
                'title' => 'How to Find Charging Stations',
                'slug' => 'how-to-find-charging-stations',
                'content' => '<h2>Finding EVON Charging Stations</h2><p>Finding a charging station is easy:</p><ol><li>Open the EVON app or visit the website</li><li>Use the map view to see all nearby stations</li><li>Filter by availability, connector type, or speed</li><li>Click on a station to see details</li></ol><p>You can also use the search function to find stations by address or name.</p>',
                'excerpt' => 'Learn how to locate EVON charging stations near you using our app or website.',
                'meta_title' => 'How to Find Charging Stations | EVON Help',
                'meta_description' => 'Discover how to find EVON charging stations using our map, filters, and search features.',
                'is_published' => true,
                'is_featured' => true,
                'order' => 2,
                'tags' => json_encode(['map', 'stations', 'location']),
            ],
            [
                'title' => 'How to Start a Charging Session',
                'slug' => 'how-to-start-charging',
                'content' => '<h2>Starting a Charging Session</h2><p>To start charging your electric vehicle:</p><ol><li>Find a charging station using the app</li><li>Connect your vehicle to the charger</li><li>Scan the QR code or tap your card</li><li>Select your payment method</li><li>Wait for confirmation and charging begins</li></ol><p>The session will automatically stop when your battery is full or you manually stop it.</p>',
                'excerpt' => 'Step-by-step guide to starting a charging session at EVON stations.',
                'meta_title' => 'How to Start Charging | EVON Help',
                'meta_description' => 'Learn how to start a charging session at EVON charging stations.',
                'is_published' => true,
                'is_featured' => true,
                'order' => 3,
                'tags' => json_encode(['charging', 'session', 'start']),
            ],
        ];

        foreach ($gettingStartedArticles as $article) {
            HelpArticle::updateOrCreate(
                ['slug' => $article['slug']],
                array_merge($article, ['category_id' => $categoryIds['getting-started']])
            );
        }

        // Create articles for FAQ
        $faqArticles = [
            [
                'title' => 'How much does charging cost?',
                'slug' => 'how-much-does-charging-cost',
                'content' => '<h2>EVON Charging Costs</h2><p>Our pricing is transparent and competitive:</p><ul><li><strong>Per kWh pricing:</strong> €0.35 - €0.45 per kWh depending on location</li><li><strong>Session fee:</strong> €1.00 per session</li><li><strong>Parking fees:</strong> May apply at certain locations</li></ul><p>You can see the exact price before starting any charging session in the app.</p>',
                'excerpt' => 'Learn about EVON charging pricing and costs per session.',
                'meta_title' => 'How Much Does Charging Cost? | EVON FAQ',
                'meta_description' => 'Find out about EVON charging costs and pricing.',
                'is_published' => true,
                'is_featured' => true,
                'order' => 1,
                'tags' => json_encode(['pricing', 'cost', 'fees']),
            ],
            [
                'title' => 'What payment methods are accepted?',
                'slug' => 'payment-methods',
                'content' => '<h2>Accepted Payment Methods</h2><p>EVON accepts multiple payment options:</p><ul><li>Credit/Debit Cards (Visa, Mastercard, Amex)</li><li>PayPal</li><li>Apple Pay</li><li>Google Pay</li><li>EVON Prepaid Credits</li><li>Bank Transfer (for business accounts)</li></ul><p>You can add and manage payment methods in your account settings.</p>',
                'excerpt' => 'Learn about the various payment methods accepted by EVON.',
                'meta_title' => 'Payment Methods | EVON FAQ',
                'meta_description' => 'Discover all payment methods accepted for EVON charging.',
                'is_published' => true,
                'is_featured' => false,
                'order' => 2,
                'tags' => json_encode(['payment', 'credit card', 'paypal']),
            ],
            [
                'title' => 'How do I stop a charging session?',
                'slug' => 'how-to-stop-charging',
                'content' => '<h2>Stopping Your Charging Session</h2><p>To stop a charging session:</p><ol><li>Open the EVON app</li><li>Find the active session</li><li>Tap "Stop Charging"</li><li>Wait for the charger to disconnect</li><li>Receive your invoice via email</li></ol><p>You can also stop by tapping your card on the charger again.</p>',
                'excerpt' => 'Learn how to properly stop your EVON charging session.',
                'meta_title' => 'How to Stop Charging | EVON FAQ',
                'meta_description' => 'Guide to stopping your EVON charging session.',
                'is_published' => true,
                'is_featured' => false,
                'order' => 3,
                'tags' => json_encode(['stop', 'end', 'session']),
            ],
        ];

        foreach ($faqArticles as $article) {
            HelpArticle::updateOrCreate(
                ['slug' => $article['slug']],
                array_merge($article, ['category_id' => $categoryIds['faq']])
            );
        }

        // Create articles for Troubleshooting
        $troubleshootingArticles = [
            [
                'title' => 'Charger not responding',
                'slug' => 'charger-not-responding',
                'content' => '<h2>Charger Not Responding</h2><p>If the charger is not responding:</p><ol><li>Check that the charging cable is properly connected</li><li>Try restarting the session in the app</li><li>Wait 2-3 minutes and try again</li><li>Check for any error messages on the charger display</li><li>Contact support if the issue persists</li></ol><p>Most issues can be resolved by reconnecting the cable or restarting the session.</p>',
                'excerpt' => 'Troubleshooting guide when your EVON charger is not responding.',
                'meta_title' => 'Charger Not Responding | EVON Troubleshooting',
                'meta_description' => 'Fix issues when your EVON charger is not responding.',
                'is_published' => true,
                'is_featured' => true,
                'order' => 1,
                'tags' => json_encode(['charger', 'error', 'not responding']),
            ],
            [
                'title' => 'Payment failed',
                'slug' => 'payment-failed',
                'content' => '<h2>Payment Failed</h2><p>If your payment fails:</p><ol><li>Verify your card has sufficient funds</li><li>Check that your card is not expired</li><li>Ensure the card is enabled for online payments</li><li>Try a different payment method</li><li>Contact your bank if issues persist</li></ol><p>You can also add a new payment method in your account settings.</p>',
                'excerpt' => 'What to do when your EVON payment fails.',
                'meta_title' => 'Payment Failed | EVON Troubleshooting',
                'meta_description' => 'Resolve payment issues with EVON charging.',
                'is_published' => true,
                'is_featured' => false,
                'order' => 2,
                'tags' => json_encode(['payment', 'failed', 'card']),
            ],
            [
                'title' => 'Charging speed is slow',
                'slug' => 'slow-charging',
                'content' => '<h2>Slow Charging Speed</h2><p>Several factors can affect charging speed:</p><ul><li><strong>Battery temperature:</strong> Cold batteries charge slower</li><li><strong>Battery level:</strong> Charging slows as battery fills</li><li><strong>Station capacity:</strong> Some stations have lower power</li><li><strong>Grid load:</strong> High demand can reduce speed</li></ul><p>Check the station details in the app for expected charging times.</p>',
                'excerpt' => 'Understand why your EVON charging might be slower than expected.',
                'meta_title' => 'Slow Charging | EVON Troubleshooting',
                'meta_description' => 'Why is my EVON charging slow? Find out the reasons.',
                'is_published' => true,
                'is_featured' => false,
                'order' => 3,
                'tags' => json_encode(['slow', 'speed', 'charging time']),
            ],
        ];

        foreach ($troubleshootingArticles as $article) {
            HelpArticle::updateOrCreate(
                ['slug' => $article['slug']],
                array_merge($article, ['category_id' => $categoryIds['troubleshooting']])
            );
        }

        // Create articles for Billing & Payments
        $billingArticles = [
            [
                'title' => 'How to view my invoices',
                'slug' => 'view-invoices',
                'content' => '<h2>Viewing Your Invoices</h2><p>To view your invoices:</p><ol><li>Log into your EVON account</li><li>Go to Settings > Billing</li><li>Click on "Invoices"</li><li>Download or view any invoice</li></ol><p>All invoices are also sent to your registered email address after each session.</p>',
                'excerpt' => 'Learn how to access and download your EVON invoices.',
                'meta_title' => 'View Invoices | EVON Billing',
                'meta_description' => 'How to view and download your EVON invoices.',
                'is_published' => true,
                'is_featured' => false,
                'order' => 1,
                'tags' => json_encode(['invoice', 'billing', 'receipt']),
            ],
        ];

        foreach ($billingArticles as $article) {
            HelpArticle::updateOrCreate(
                ['slug' => $article['slug']],
                array_merge($article, ['category_id' => $categoryIds['billing-payments']])
            );
        }

        // Create articles for Account Management
        $accountArticles = [
            [
                'title' => 'How to change my password',
                'slug' => 'change-password',
                'content' => '<h2>Changing Your Password</h2><p>To change your password:</p><ol><li>Go to Settings > Security</li><li>Click on "Change Password"</li><li>Enter your current password</li><li>Enter your new password</li><li>Confirm the new password</li><li>Click "Save Changes"</li></ol><p>Make sure your password is strong and unique.</p>',
                'excerpt' => 'Step-by-step guide to changing your EVON account password.',
                'meta_title' => 'Change Password | EVON Account',
                'meta_description' => 'How to change your EVON account password.',
                'is_published' => true,
                'is_featured' => false,
                'order' => 1,
                'tags' => json_encode(['password', 'security', 'account']),
            ],
        ];

        foreach ($accountArticles as $article) {
            HelpArticle::updateOrCreate(
                ['slug' => $article['slug']],
                array_merge($article, ['category_id' => $categoryIds['account-management']])
            );
        }

        $this->command->info('Help categories and articles created successfully!');
    }
}
