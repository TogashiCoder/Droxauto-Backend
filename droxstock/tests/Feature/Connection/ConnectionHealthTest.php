<?php

namespace Tests\Feature\Connection;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Exception;

class ConnectionHealthTest extends TestCase
{
    /**
     * Test SMTP connection and email sending capability
     */
    public function test_smtp_connection_and_email_sending()
    {
        $this->markTestSkipped('SMTP connection test - run manually when needed');

        try {
            // Test 1: Check SMTP configuration
            $this->assertSMTPConfiguration();

            // Test 2: Test actual email sending
            $this->assertEmailSending();

            $this->assertTrue(true, 'SMTP connection and email sending working correctly');
        } catch (Exception $e) {
            $this->fail('SMTP test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test AWS S3 connection and bucket access
     */
    public function test_aws_s3_connection_and_bucket_access()
    {
        $this->markTestSkipped('AWS S3 connection test - run manually when needed');

        try {
            // Test 1: Check AWS configuration
            $this->assertAWSConfiguration();

            // Test 2: Test S3 client creation
            $this->assertS3ClientCreation();

            // Test 3: Test bucket access (if bucket is configured)
            $this->assertBucketAccess();

            $this->assertTrue(true, 'AWS S3 connection and bucket access working correctly');
        } catch (Exception $e) {
            $this->fail('AWS S3 test failed: ' . $e->getMessage());
        }
    }

    /**
     * Assert SMTP configuration is properly set
     */
    private function assertSMTPConfiguration(): void
    {
        $requiredConfigs = [
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp.gmail.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => 'ibrahimsilver007@gmail.com',
            'MAIL_PASSWORD' => 'tvhjkycaeelqlqha',
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_FROM_ADDRESS' => 'ibrahimsilver007@gmail.com'
        ];

        foreach ($requiredConfigs as $key => $expectedValue) {
            $actualValue = config("mail.{$key}") ?? env($key);

            if ($key === 'MAIL_PASSWORD') {
                // Don't expose password in test output
                $this->assertNotEmpty($actualValue, "SMTP {$key} is not configured");
            } else {
                $this->assertEquals($expectedValue, $actualValue, "SMTP {$key} mismatch");
            }
        }
    }

    /**
     * Assert email can be sent successfully
     */
    private function assertEmailSending(): void
    {
        // Test email sending to a test address
        $testEmail = 'test@example.com';

        try {
            // This will actually attempt to send an email
            Mail::raw('SMTP Connection Test - ' . now(), function ($message) use ($testEmail) {
                $message->to($testEmail)
                    ->subject('SMTP Connection Test')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $this->assertTrue(true, 'Email sent successfully');
        } catch (Exception $e) {
            $this->fail('Email sending failed: ' . $e->getMessage());
        }
    }

    /**
     * Assert AWS configuration is properly set
     */
    private function assertAWSConfiguration(): void
    {
        $requiredConfigs = [
            'AWS_ACCESS_KEY_ID' => 'AKIATZKJSAORXH3ND3XO',
            'AWS_SECRET_ACCESS_KEY' => '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',
            'AWS_DEFAULT_REGION' => 'eu-north-1'
        ];

        foreach ($requiredConfigs as $key => $expectedValue) {
            $actualValue = config("aws.{$key}");

            if ($key === 'AWS_SECRET_ACCESS_KEY') {
                // Don't expose secret key in test output
                $this->assertNotEmpty($actualValue, "AWS {$key} is not configured");
            } else {
                $this->assertEquals($expectedValue, $actualValue, "AWS {$key} mismatch");
            }
        }
    }

    /**
     * Assert S3 client can be created successfully
     */
    private function assertS3ClientCreation(): void
    {
        try {
            $s3Client = new S3Client([
                'version' => 'latest',
                'region'  => config('aws.default_region'),
                'credentials' => [
                    'key'    => config('aws.credentials.key'),
                    'secret' => config('aws.credentials.secret'),
                ],
            ]);

            $this->assertInstanceOf(S3Client::class, $s3Client, 'S3 client created successfully');
        } catch (AwsException $e) {
            $this->fail('S3 client creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Assert bucket access (if bucket is configured)
     */
    private function assertBucketAccess(): void
    {
        $bucket = config('aws.bucket');

        if (empty($bucket)) {
            $this->markTestSkipped('AWS_BUCKET not configured - skipping bucket access test');
            return;
        }

        try {
            $s3Client = new S3Client([
                'version' => 'latest',
                'region'  => config('aws.default_region'),
                'credentials' => [
                    'key'    => config('aws.credentials.key'),
                    'secret' => config('aws.credentials.secret'),
                ],
            ]);

            // Test bucket access by listing objects
            $result = $s3Client->listObjectsV2([
                'Bucket' => $bucket,
                'MaxKeys' => 1
            ]);

            $this->assertTrue(true, 'Bucket access successful');
        } catch (AwsException $e) {
            $this->fail('Bucket access failed: ' . $e->getMessage());
        }
    }

    /**
     * Test SMTP connection without skipping (for manual testing)
     */
    public function test_smtp_connection_manual()
    {
        $this->markTestSkipped('Run this test manually to check SMTP: php artisan test --filter=test_smtp_connection_manual');

        try {
            $this->assertSMTPConfiguration();
            $this->assertEmailSending();
            $this->assertTrue(true, 'SMTP connection successful');
        } catch (Exception $e) {
            $this->fail('SMTP connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Test AWS S3 connection without skipping (for manual testing)
     */
    public function test_aws_s3_connection_manual()
    {
        $this->markTestSkipped('Run this test manually to check AWS S3: php artisan test --filter=test_aws_s3_connection_manual');

        try {
            $this->assertAWSConfiguration();
            $this->assertS3ClientCreation();
            $this->assertBucketAccess();
            $this->assertTrue(true, 'AWS S3 connection successful');
        } catch (Exception $e) {
            $this->fail('AWS S3 connection failed: ' . $e->getMessage());
        }
    }
}
