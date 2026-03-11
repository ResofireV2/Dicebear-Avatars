<?php

/*
 * This file is part of resofire/dicebear.
 *
 * Copyright (c) 2025 Resofire.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Resofire\Dicebear;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AvatarUploader;
use Flarum\User\User;
use Intervention\Image\ImageManager;

class AvatarFetcher
{
    protected SettingsRepositoryInterface $settings;
    protected AvatarUploader $uploader;
    protected ImageManager $imageManager;

    public function __construct(
        SettingsRepositoryInterface $settings,
        AvatarUploader $uploader,
        ImageManager $imageManager
    ) {
        $this->settings = $settings;
        $this->uploader = $uploader;
        $this->imageManager = $imageManager;
    }

    /**
     * Build the Dicebear URL for a given user.
     */
    public function buildUrl(User $user): string
    {
        return rtrim($this->settings->get('resofire-dicebear.api_url'), '/')
            . '/9.x/'
            . $this->settings->get('resofire-dicebear.avatar_style')
            . '/png?seed='
            . urlencode($user->username);
    }

    /**
     * Fetch the Dicebear PNG from the API, save it to assets/avatars via
     * Flarum's AvatarUploader, and persist the user record.
     *
     * @throws \RuntimeException if the HTTP request fails or returns non-200.
     */
    public function fetchAndSave(User $user): void
    {
        $url = $this->buildUrl($user);

        // Use a stream context so we get a proper error on failure.
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header'  => "User-Agent: resofire-dicebear/1.0\r\n",
            ],
        ]);

        $imageData = @file_get_contents($url, false, $context);

        if ($imageData === false || strlen($imageData) === 0) {
            throw new \RuntimeException("Failed to fetch Dicebear avatar from: $url");
        }

        // Build an Intervention Image instance from the raw binary data.
        $image = $this->imageManager->make($imageData);

        // AvatarUploader::upload() resizes to 100×100, saves to
        // assets/avatars/<random>.png, and calls $user->changeAvatarPath().
        $this->uploader->upload($user, $image);

        $user->save();
    }
}
