<?php

namespace App\Services;

use App\Models\VinstackSetting;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CloudinaryService
{
    public function __construct(
        protected ?UploadApi $uploadApi = null,
    ) {}

    public function isConfigured(): bool
    {
        $config = $this->resolveConfig();

        return filled($config['cloud_name'])
            && filled($config['api_key'])
            && (filled($config['api_secret']) || filled($config['upload_preset']));
    }

    /**
     * @return array{cloud_name: ?string, api_key: ?string, api_secret: ?string, upload_preset: ?string, folder: ?string}
     */
    public function resolveConfig(): array
    {
        $settings = VinstackSetting::current();

        return [
            'cloud_name' => $settings->cloudinary_cloud_name ?: config('services.cloudinary.cloud_name'),
            'api_key' => $settings->cloudinary_api_key ?: config('services.cloudinary.api_key'),
            'api_secret' => $settings->cloudinary_api_secret ?: config('services.cloudinary.api_secret'),
            'upload_preset' => $settings->cloudinary_upload_preset ?: config('services.cloudinary.upload_preset'),
            'folder' => $settings->cloudinary_folder ?: config('services.cloudinary.folder', 'vinstack/containers'),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{url: string, public_id: string, secure_url: string}
     */
    public function upload(UploadedFile|string $file, array $options = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Cloudinary is not configured.');
        }

        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            throw new RuntimeException('Upload file is not readable.');
        }

        $config = $this->resolveConfig();
        $uploadOptions = array_merge([
            'resource_type' => 'image',
        ], $options);

        if (filled($config['upload_preset'])) {
            $uploadOptions['upload_preset'] = $config['upload_preset'];
        }

        $result = $this->uploadApiInstance($config)->upload($path, $uploadOptions);
        $data = $this->normalizeUploadResult($result);

        return [
            'url' => (string) ($data['secure_url'] ?? $data['url'] ?? ''),
            'secure_url' => (string) ($data['secure_url'] ?? $data['url'] ?? ''),
            'public_id' => (string) ($data['public_id'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeUploadResult(mixed $result): array
    {
        if ($result instanceof \Cloudinary\Api\ApiResponse) {
            return $result->getArrayCopy();
        }

        if (is_array($result)) {
            return $result;
        }

        return (array) $result;
    }

    public function probe(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Cloudinary credentials are incomplete. Set cloud name, API key, and API secret (or upload preset).',
            ];
        }

        $config = $this->resolveConfig();

        return [
            'ok' => true,
            'message' => 'Cloudinary credentials present.',
            'cloud_name' => $config['cloud_name'],
            'has_api_secret' => filled($config['api_secret']),
            'has_upload_preset' => filled($config['upload_preset']),
            'folder' => $config['folder'],
        ];
    }

    /**
     * @param  array{cloud_name: ?string, api_key: ?string, api_secret: ?string, upload_preset: ?string, folder: ?string}  $config
     */
    protected function uploadApiInstance(array $config): UploadApi
    {
        if ($this->uploadApi !== null) {
            return $this->uploadApi;
        }

        $cloudConfig = [
            'cloud' => [
                'cloud_name' => $config['cloud_name'],
                'api_key' => $config['api_key'],
            ],
            'url' => [
                'secure' => true,
            ],
        ];

        if (filled($config['api_secret'])) {
            $cloudConfig['cloud']['api_secret'] = $config['api_secret'];
        }

        Configuration::instance($cloudConfig);

        return (new Cloudinary(Configuration::instance()))->uploadApi();
    }
}
