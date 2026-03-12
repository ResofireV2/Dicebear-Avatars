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

use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FlushAvatarsController implements RequestHandlerInterface
{
    protected Paths $paths;

    public function __construct(Paths $paths)
    {
        $this->paths = $paths;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $avatarDir = $this->paths->public . '/assets/avatars';

        // Find all users whose avatar_url points to a local PNG file
        // (i.e. saved by this extension — not a full URL).
        $users = User::whereNotNull('avatar_url')
            ->where('avatar_url', 'not like', 'http%')
            ->where('avatar_url', 'like', '%.png')
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $file = $avatarDir . '/' . $user->avatar_url;

            if (is_file($file)) {
                unlink($file);
            }

            User::where('id', $user->id)->update(['avatar_url' => null]);
            $count++;
        }

        return new JsonResponse(['flushed' => $count]);
    }
}
