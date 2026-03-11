<?php

/*
 * This file is part of resofire/dicebear.
 *
 * Copyright (c) 2025 Resofire.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Resofire\Dicebear\Api;

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Resofire\Dicebear\AvatarFetcher;

class AddDicebearAvatar
{
    protected SettingsRepositoryInterface $settings;
    protected AvatarFetcher $fetcher;

    public function __construct(SettingsRepositoryInterface $settings, AvatarFetcher $fetcher)
    {
        $this->settings = $settings;
        $this->fetcher = $fetcher;
    }

    public function __invoke(BasicUserSerializer $serializer, User $user, array $attributes): array
    {
        // If the user already has a locally saved avatar, do nothing.
        if (!empty($attributes['avatarUrl'])) {
            return $attributes;
        }

        // Try to download and save locally now (lazy fallback for existing users).
        try {
            $this->fetcher->fetchAndSave($user);

            // After saving, avatar_url holds the filename (e.g. "abc123.png").
            // The getAvatarUrlAttribute accessor converts it to a full URL.
            $attributes['avatarUrl'] = $user->avatar_url
                ? $user->getAvatarUrlAttribute($user->getRawOriginal('avatar_url'))
                : null;
        } catch (\Throwable $e) {
            // Fetching failed — fall back to the remote Dicebear URL so the
            // user still sees an avatar. Will retry on next page load.
            $attributes['avatarUrl'] = rtrim($this->settings->get('resofire-dicebear.api_url'), '/')
                . '/9.x/'
                . $this->settings->get('resofire-dicebear.avatar_style')
                . '/png?seed='
                . urlencode($user->username);
        }

        return $attributes;
    }
}
