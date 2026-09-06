<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class PrivacyTermsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['type' => 'privacy_policy', 'key' => 'privacy_policy'],
            ['value' => $this->userPrivacyPolicyHtml()]
        );

        Setting::updateOrCreate(
            ['type' => 'privacy_policy_rider', 'key' => 'privacy_policy_rider'],
            ['value' => $this->riderPrivacyPolicyHtml()]
        );

        Setting::updateOrCreate(
            ['type' => 'terms_condition', 'key' => 'terms_condition'],
            ['value' => $this->userTermsHtml()]
        );

        Setting::updateOrCreate(
            ['type' => 'terms_condition_rider', 'key' => 'terms_condition_rider'],
            ['value' => $this->riderTermsHtml()]
        );
    }

    private function userPrivacyPolicyHtml(): string
    {
        return <<<'HTML'
<p><strong>Point Delivery</strong> (“we”, “us”) operates the <strong>Point User</strong> customer application. This Privacy Policy explains how we collect, use, store, and protect personal data when you create an account, place delivery orders, track parcels, and make payments.</p>

<h3>1. Information We Collect</h3>
<ul>
<li>Account details: name, username, phone number, email, and address</li>
<li>Delivery details: pickup/drop-off addresses, region, township, city, and order notes</li>
<li>Payment details: KBZ Pay name/number and payment records needed to settle orders</li>
<li>Order history, chat/support messages, photos, and signatures when required for delivery proof</li>
<li>Location data (when permitted) to support pickup and delivery accuracy</li>
<li>Device and app data: app version, device identifiers, and notification player IDs</li>
</ul>

<h3>2. How We Use Your Information</h3>
<ul>
<li>Create and secure your Point User account</li>
<li>Process orders, assign riders, and share status updates</li>
<li>Handle payments, invoices, and customer support</li>
<li>Improve service quality, prevent fraud, and maintain platform security</li>
</ul>

<h3>3. Sharing</h3>
<p>We share order and contact details with assigned riders and authorized Point Delivery staff only as needed to complete delivery. We do not sell your personal data. We may disclose information when required by law.</p>

<h3>4. Storage &amp; Security</h3>
<p>Data is stored on secured servers with access limited to authorized personnel. No method of transmission over the internet is 100% secure, but we take reasonable safeguards to protect your information.</p>

<h3>5. Your Rights</h3>
<ul>
<li>View and update your profile information in the app</li>
<li>Request account deletion from in-app settings or Customer Support</li>
<li>Contact support about privacy questions or data access requests</li>
</ul>

<h3>6. Children</h3>
<p>Point User is not directed at children under 13. We do not knowingly collect personal data from children under 13.</p>

<h3>7. Changes</h3>
<p>We may update this policy from time to time. Updates will be published in the app and/or on our website. Continued use means you accept the updated policy.</p>

<h3>8. Contact</h3>
<p>For privacy questions, contact Point Delivery Customer Support via the Point User app or our website.</p>
<p>Last updated: 4 September 2026</p>
HTML;
    }

    private function riderPrivacyPolicyHtml(): string
    {
        return <<<'HTML'
<p><strong>Point Delivery</strong> (“we”, “us”) operates the <strong>Point Delivery Partner</strong> rider application. This Privacy Policy explains how we collect, use, store, and protect personal data when you register as a rider, accept jobs, navigate routes, upload delivery proof, and settle remittances.</p>

<h3>1. Information We Collect</h3>
<ul>
<li>Rider profile: name, username, phone number, email, address, and branch assignment</li>
<li>Identity / work details needed for partner onboarding and operations</li>
<li>Job data: assigned orders, pickup/delivery addresses, status updates, and remittance records</li>
<li>Location data (when permitted) for pickup, navigation, and delivery confirmation</li>
<li>Camera and gallery access (when permitted) to upload parcel photos, customer photos, and signatures</li>
<li>Payment details used for rider payouts and remittance reconciliation (e.g. KBZ Pay)</li>
<li>Device and app data: app version, device identifiers, and notification player IDs</li>
</ul>

<h3>2. How We Use Your Information</h3>
<ul>
<li>Create and manage your rider partner account</li>
<li>Assign deliveries, track job progress, and communicate with customers/office staff</li>
<li>Verify delivery completion with photos, signatures, and status logs</li>
<li>Process remittances, fuel/fee calculations, and partner payouts</li>
<li>Maintain safety, prevent fraud, and improve rider operations</li>
</ul>

<h3>3. Sharing</h3>
<p>Customer order details are shown to you only for assigned jobs. Your rider identity and contact details may be shared with customers and office staff as needed for pickup/delivery. We do not sell your personal data. We may disclose information when required by law.</p>

<h3>4. Storage &amp; Security</h3>
<p>Rider operational data is stored on secured servers with role-based access. Location, camera, and gallery permissions are requested only for delivery workflows and can be managed in device settings.</p>

<h3>5. Your Rights</h3>
<ul>
<li>View and update your rider profile in the app</li>
<li>Request account deletion or data assistance via Customer Support / office admin</li>
<li>Control location and media permissions in your device settings</li>
</ul>

<h3>6. Children</h3>
<p>The Rider app is intended for adult delivery partners. We do not knowingly onboard children under 13.</p>

<h3>7. Changes</h3>
<p>We may update this policy from time to time. Updates will be published in the app and/or on our website. Continued use means you accept the updated policy.</p>

<h3>8. Contact</h3>
<p>For privacy questions, contact Point Delivery Support via the Rider app or our website.</p>
<p>Last updated: 4 September 2026</p>
HTML;
    }

    private function userTermsHtml(): string
    {
        return <<<'HTML'
<p>By using the <strong>Point User</strong> app and Point Delivery services, you agree to these Terms.</p>

<h3>1. Account</h3>
<ul>
<li>Provide accurate username, password, name, phone number, and address.</li>
<li>Keep your login credentials confidential.</li>
<li>You are responsible for delays caused by incorrect order or contact information.</li>
</ul>

<h3>2. Service Use</h3>
<ul>
<li>Point Delivery provides parcel pickup and delivery services.</li>
<li>Prohibited or illegal items may not be shipped.</li>
<li>Pickup and delivery times may change due to traffic, weather, or address conditions.</li>
</ul>

<h3>3. Payments</h3>
<ul>
<li>Payments may be made via KBZ Pay or other supported methods.</li>
<li>Enter correct payment details. Report payment issues to Support immediately.</li>
</ul>

<h3>4. Liability</h3>
<ul>
<li>Failed delivery due to wrong address or unavailable recipient may require rescheduling.</li>
<li>Point Delivery is not liable for events beyond reasonable control (e.g. natural disasters, network outages).</li>
</ul>

<h3>5. Prohibited Conduct</h3>
<ul>
<li>Using another person’s account without permission</li>
<li>Attempting to disrupt systems or steal data</li>
<li>Submitting fraudulent orders or information</li>
</ul>

<h3>6. Contact</h3>
<p>For questions, contact Point Delivery Customer Support via the Point User app or our website.</p>
<p>Last updated: 4 September 2026</p>
HTML;
    }

    private function riderTermsHtml(): string
    {
        return <<<'HTML'
<p>By using the <strong>Point Delivery Partner</strong> rider app, you agree to these Terms as an independent delivery partner of Point Delivery.</p>

<h3>1. Partner Account</h3>
<ul>
<li>Provide accurate personal and work details required for onboarding.</li>
<li>Keep your account secure and do not share login credentials.</li>
<li>You must follow office/branch assignment and operating instructions.</li>
</ul>

<h3>2. Job Performance</h3>
<ul>
<li>Accept and complete assigned pickups/deliveries professionally and on time when possible.</li>
<li>Handle parcels carefully and upload required delivery proof (photo/signature) when requested.</li>
<li>Do not misuse customer personal information obtained through assigned jobs.</li>
</ul>

<h3>3. Remittance &amp; Payments</h3>
<ul>
<li>Remit collected amounts according to Point Delivery procedures and deadlines.</li>
<li>Fuel, fees, and payouts follow office rules and app records.</li>
<li>Report remittance mismatches to the office immediately.</li>
</ul>

<h3>4. Safety &amp; Conduct</h3>
<ul>
<li>Follow traffic and safety laws while performing delivery work.</li>
<li>Harassment, fraud, or misuse of the platform may result in suspension or termination.</li>
</ul>

<h3>5. Changes</h3>
<p>Point Delivery may update these Terms. Continued use of the Rider app means you accept the updated Terms.</p>

<h3>6. Contact</h3>
<p>For questions, contact Point Delivery Support via the Rider app or our website.</p>
<p>Last updated: 4 September 2026</p>
HTML;
    }
}