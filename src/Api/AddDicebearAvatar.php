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

        // Try to download and save it locally now (lazy fallback for existing users).
        try {
            $this->fetcher->fetchAndSave($user);
            // Reload the avatar URL from the now-updated user model.
            $user->refresh();
            $attributes['avatarUrl'] = $user->avatar_url
                ? (string) $user->avatarUrl
                : null;
        } catch (\Throwable $e) {
            // If fetching fails (e.g. network down), fall back to the remote URL
            // so the user still sees an avatar.
            $attributes['avatarUrl'] = rtrim($this->settings->get('resofire-dicebear.api_url'), '/')
                . '/9.x/'
                . $this->settings->get('resofire-dicebear.avatar_style')
                . '/png?seed='
                . urlencode($user->username);
        }

        return $attributes;
    }
}
