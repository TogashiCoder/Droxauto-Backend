<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Exception;

class TestConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:connections {--type=all : Type of connection to test (smtp, aws, or all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMTP and AWS connections';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        
        $this->info('🔍 Testing Connections...');
        $this->newLine();

        if ($type === 'all' || $type === 'smtp') {
            $this->testSMTPConnection();
        }

        if ($type === 'all' || $type === 'aws') {
            $this->testAWSConnection();
        }

        $this->info('✅ Connection testing completed!');
    }

    /**
     * Test SMTP connection
     */
    private function testSMTPConnection(): void
    {
        $this->info('📧 Testing SMTP Connection...');
        
        try {
            // Check configuration
            $this->checkSMTPConfiguration();
            
            // Test actual email sending
            $this->testEmailSending();
            
            $this->info('✅ SMTP connection successful!');
            
        } catch (Exception $e) {
            $this->error('❌ SMTP connection failed: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    /**
     * Check SMTP configuration
     */
    private function checkSMTPConfiguration(): void
    {
        $this->line('  Checking SMTP configuration...');
        
        $configs = [
            'MAIL_MAILER' => config('mail.default'),
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_PORT' => config('mail.mailers.smtp.port'),
            'MAIL_USERNAME' => config('mail.mailers.smtp.username'),
            'MAIL_ENCRYPTION' => config('mail.mailers.smtp.encryption'),
            'MAIL_FROM_ADDRESS' => config('mail.from.address')
        ];

        foreach ($configs as $key => $value) {
            if (empty($value)) {
                throw new Exception("Missing SMTP configuration: {$key}");
            }
            $this->line("    ✓ {$key}: {$value}");
        }
        
        $this->line('  ✓ SMTP configuration is complete');
    }

    /**
     * Test actual email sending
     */
    private function testEmailSending(): void
    {
        $this->line('  Testing email sending...');
        
        $testEmail = 'test@example.com';
        
        Mail::raw('SMTP Connection Test - ' . now(), function($message) use ($testEmail) {
            $message->to($testEmail)
                    ->subject('SMTP Connection Test')
                    ->from(config('mail.from.address'), config('mail.from.name'));
        });
        
        $this->line('  ✓ Test email sent successfully');
    }

    /**
     * Test AWS connection
     */
    private function testAWSConnection(): void
    {
        $this->info('☁️  Testing AWS Connection...');
        
        try {
            // Check configuration
            $this->checkAWSConfiguration();
            
            // Test S3 client creation
            $this->testS3Client();
            
            $this->info('✅ AWS connection successful!');
            
        } catch (Exception $e) {
            $this->error('❌ AWS connection failed: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    /**
     * Check AWS configuration
     */
    private function checkAWSConfiguration(): void
    {
        $this->line('  Checking AWS configuration...');
        
        $configs = [
            'AWS_ACCESS_KEY_ID' => env('AWS_ACCESS_KEY_ID'),
            'AWS_SECRET_ACCESS_KEY' => env('AWS_SECRET_ACCESS_KEY'),
            'AWS_DEFAULT_REGION' => env('AWS_DEFAULT_REGION'),
            'AWS_BUCKET' => env('AWS_BUCKET')
        ];

        foreach ($configs as $key => $value) {
            if (empty($value) && $key !== 'AWS_BUCKET') {
                throw new Exception("Missing AWS configuration: {$key}");
            }
            
            if ($key === 'AWS_SECRET_ACCESS_KEY') {
                $this->line("    ✓ {$key}: " . str_repeat('*', 8) . substr($value, -4));
            } else {
                $this->line("    ✓ {$key}: {$value}");
            }
        }
        
        $this->line('  ✓ AWS configuration is complete');
    }

    /**
     * Test S3 client creation
     */
    private function testS3Client(): void
    {
        $this->line('  Testing S3 client creation...');
        
        // Check if AWS SDK is available
        if (!class_exists('Aws\S3\S3Client')) {
            throw new Exception('AWS SDK not installed. Run: composer require aws/aws-sdk-php');
        }
        
        $s3Client = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
        
        $this->line('  ✓ S3 client created successfully');
        
        // Test bucket access if configured
        $bucket = env('AWS_BUCKET');
        if (!empty($bucket)) {
            $this->line('  Testing bucket access...');
            
            try {
                $result = $s3Client->listObjectsV2([
                    'Bucket' => $bucket,
                    'MaxKeys' => 1
                ]);
                $this->line('  ✓ Bucket access successful');
            } catch (\Aws\Exception\AwsException $e) {
                $this->warn('  ⚠️  Bucket access failed: ' . $e->getMessage());
            }
        } else {
            $this->line('  ℹ️  AWS_BUCKET not configured - skipping bucket access test');
        }
    }
}
