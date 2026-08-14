<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\FrontendData;
use App\Models\Setting;
use App\Models\WebsiteSection;
use App\Models\WebsiteSectionTitle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FrontendWebsiteSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('settings')->delete();
        DB::table('frontend_data')->delete();
        DB::table('website_section_titles')->delete();
        DB::table('website_sections')->delete();
        DB::table('media')->delete();

        $this->seedTextSettings();
        $this->seedImageSettings();
        $this->seedAppSettingMedia();
        $this->seedWhyChoose();
        $this->seedClientTestimonials();
        $this->seedWebsiteSections();
        $this->seedWalkthrough();
    }

    private function seedTextSettings(): void
    {
        $settings = [
            'app_content' => [
                'app_name' => 'Point Delivery',
                'app_title' => 'Fast & Reliable Local Delivery',
                'app_subtitle' => 'At Your Doorstep',
                'play_store_link' => 'https://play.google.com/store',
                'app_store_link' => 'https://apps.apple.com',
                'trust_pilot_link' => 'https://www.trustpilot.com',
            ],
            'why_choose' => [
                'title' => 'Why Choose',
                'description' => 'We provide fast, secure, and affordable local delivery services with real-time tracking and professional delivery partners.',
            ],
            'app_overview' => [
                'title' => 'Everything You Need for',
                'subtitle' => 'Seamless Deliveries',
            ],
            'client_review' => [
                'client_review_title' => 'What Our Clients Say',
            ],
            'download_app' => [
                'download_title' => 'Download the',
                'download_subtitle' => 'Point Delivery App',
                'download_description' => 'Order deliveries, track packages in real time, and manage all your shipments from one easy-to-use mobile app.',
                'download_footer_content' => 'Experience fast and reliable delivery services at your fingertips.',
            ],
            'client_testimonial' => [
                'title' => 'Trusted by',
                'subtitle' => 'Thousands',
                'playstore_totalreview' => '500',
                'appstore_totalreview' => '300',
                'trustpilot_totalreview' => '200',
                'playstore_review' => '4.8',
                'appstore_review' => '4.7',
                'trustpilot_review' => '4.9',
            ],
            'courier_recruitment_section' => [
                'courier_title' => 'Join Our Delivery Team',
                'courier_description' => 'Become a delivery partner and earn on your own schedule. Flexible hours, competitive pay, and full support.',
            ],
            'delivery_partner' => [
                'title' => 'Delivery Partner',
                'subtitle' => 'Program',
                'description' => 'Join our network of delivery partners and start earning today.',
            ],
            'contact_us' => [
                'contact_title' => 'Get in Touch',
                'contact_subtitle' => 'We are here to help you with any questions.',
            ],
            'about_us' => [
                'download_title' => 'About',
                'download_subtitle' => 'Point Delivery',
                'long_des' => 'Point Delivery is a modern local delivery platform connecting customers with reliable delivery partners for fast and secure package delivery.',
            ],
            'track_order' => [
                'track_order_title' => 'Track Your Order',
                'track_order_subtitle' => 'Enter your tracking number below',
                'track_page_title' => 'Order Tracking',
                'track_page_description' => 'Track your package in real time from pickup to delivery.',
            ],
            'document_verification' => [
                'title' => 'Document Verification',
                'subtitle' => 'Simple & Secure',
                'description' => 'Complete your verification quickly and start delivering.',
            ],
            'delivery_man_section' => [
                'title' => 'Delivery Partner',
                'subtitle' => 'Benefits',
            ],
            'deliver_your_way' => [
                'title' => 'Deliver',
                'subtitle' => 'Your Way',
                'description' => 'Choose your schedule and delivery areas that work best for you.',
            ],
            'delivery_job' => [
                'delivery_job_title' => 'Start Your',
                'delivery_job_subtitle' => 'Delivery Career',
                'delivery_job_description' => 'Flexible work, great earnings, and the freedom to be your own boss.',
            ],
        ];

        foreach ($settings as $type => $values) {
            foreach ($values as $key => $value) {
                Setting::create([
                    'type' => $type,
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        }
    }

    private function seedImageSettings(): void
    {
        $images = [
            'app_content' => [
                'delivery_man_image' => 'images/deliverypartnerimage.png',
                'app_logo_image' => 'images/logo.png',
                'playstore_image' => 'images/download-1.png',
                'appstore_image' => 'images/download-app.png',
            ],
            'download_app' => [
                'download_app_logo' => 'images/downloadappmage.png',
            ],
            'courier_recruitment_section' => [
                'courier_image' => 'images/recruitment.png',
            ],
            'contact_us' => [
                'contact_us_app_ss' => 'images/Contactimage.png',
            ],
            'about_us' => [
                'about_us_app_ss' => 'images/aboutimage.png',
            ],
            'delivery_job' => [
                'delivery_job_image' => 'images/delivery-job.png',
            ],
        ];

        foreach ($images as $type => $items) {
            foreach ($items as $key => $path) {
                $this->createSettingWithMedia($type, $key, $path);
            }
        }
    }

    private function seedAppSettingMedia(): void
    {
        $appSetting = AppSetting::first();
        if (!$appSetting) {
            return;
        }

        AppSetting::where('id', $appSetting->id)->update(['site_name' => 'Point Delivery']);

        $this->attachMedia($appSetting, 'site_logo', 'images/logo.png');
        $this->attachMedia($appSetting, 'site_favicon', 'images/favicon.ico');
        $this->attachMedia($appSetting, 'site_dark_logo', 'images/dark_logo.png');
    }

    private function seedWhyChoose(): void
    {
        $items = [
            [
                'title' => 'Fast Delivery',
                'subtitle' => 'Get your packages delivered quickly with our network of local delivery partners.',
                'image' => 'images/fast-delivery.png',
            ],
            [
                'title' => 'Real-Time Tracking',
                'subtitle' => 'Track your shipment live from pickup to delivery with instant status updates.',
                'image' => 'images/overview.png',
            ],
            [
                'title' => 'Secure Handling',
                'subtitle' => 'Your packages are handled with care by verified and professional delivery partners.',
                'image' => 'images/keep-dry.png',
            ],
        ];

        foreach ($items as $item) {
            $record = FrontendData::create([
                'title' => $item['title'],
                'subtitle' => $item['subtitle'],
                'type' => 'why_choose',
                'description' => null,
            ]);
            $this->attachMedia($record, 'frontend_data_image', $item['image']);
        }
    }

    private function seedClientTestimonials(): void
    {
        $items = [
            [
                'title' => 'Sarah Johnson',
                'subtitle' => 'Regular Customer',
                'description' => 'Point Delivery has completely changed how I send packages. Fast, reliable, and the tracking feature is amazing!',
                'image' => 'images/clientReviewimage.png',
            ],
            [
                'title' => 'Michael Chen',
                'subtitle' => 'Business Owner',
                'description' => 'We use Point Delivery for all our business shipments. Professional service and always on time.',
                'image' => 'images/client-testimonial.png',
            ],
            [
                'title' => 'Emily Davis',
                'subtitle' => 'Happy Customer',
                'description' => 'The app is so easy to use. I can create orders and track deliveries in seconds. Highly recommended!',
                'image' => 'frontend-website/assets/website/client1.png',
            ],
        ];

        foreach ($items as $item) {
            $record = FrontendData::create([
                'title' => $item['title'],
                'subtitle' => $item['subtitle'],
                'type' => 'client_testimonial',
                'description' => $item['description'],
            ]);
            $this->attachMedia($record, 'frontend_data_image', $item['image']);
        }
    }

    private function seedWebsiteSections(): void
    {
        $sections = [
            [
                'title' => 'Real-Time',
                'subtitle' => 'Order Tracking',
                'image' => 'images/overview.png',
                'points' => [
                    'Track your package live on the map',
                    'Get instant delivery status updates',
                    'Receive notifications at every step',
                ],
            ],
            [
                'title' => 'Easy',
                'subtitle' => 'Order Management',
                'image' => 'images/app-info.png',
                'points' => [
                    'Create orders in just a few taps',
                    'Schedule pickups at your convenience',
                    'Manage all deliveries from one place',
                ],
            ],
        ];

        foreach ($sections as $sectionData) {
            $section = WebsiteSection::create([
                'title' => $sectionData['title'],
                'subtitle' => $sectionData['subtitle'],
            ]);

            foreach ($sectionData['points'] as $point) {
                WebsiteSectionTitle::create([
                    'section_id' => $section->id,
                    'title' => $point,
                ]);
            }

            $this->attachMedia($section, 'section_image', $sectionData['image']);
        }
    }

    private function seedWalkthrough(): void
    {
        $items = [
            [
                'title' => 'Create Orders Easily',
                'subtitle' => 'Place delivery orders in just a few simple steps.',
                'image' => 'frontend-website/assets/website/dummy_images/ic_walkthrough1.png',
            ],
            [
                'title' => 'Track in Real Time',
                'subtitle' => 'Follow your delivery live from pickup to drop-off.',
                'image' => 'frontend-website/assets/website/dummy_images/ic_mobile.jpg',
            ],
            [
                'title' => 'Fast & Reliable',
                'subtitle' => 'Get your packages delivered quickly and securely.',
                'image' => 'frontend-website/assets/website/dummy_images/ic_deliveryboy.jpg',
            ],
        ];

        foreach ($items as $item) {
            $record = FrontendData::create([
                'title' => $item['title'],
                'subtitle' => $item['subtitle'],
                'type' => 'walkthrough',
                'description' => null,
            ]);
            $this->attachMedia($record, 'frontend_data_image', $item['image']);
        }
    }

    private function createSettingWithMedia(string $type, string $key, string $publicPath): void
    {
        $setting = Setting::updateOrCreate(
            ['type' => $type, 'key' => $key],
            ['value' => '']
        );

        $this->attachMedia($setting, $key, $publicPath);
    }

    private function attachMedia($model, string $collection, string $publicPath): void
    {
        $fullPath = public_path($publicPath);

        if (!file_exists($fullPath)) {
            return;
        }

        $model->clearMediaCollection($collection);
        $model->addMedia($fullPath)
            ->preservingOriginal()
            ->toMediaCollection($collection);
    }
}
