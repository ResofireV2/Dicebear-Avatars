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

        // Step 1: Clear avatar_url for all users whose value is a local filename
        // (not a full URL). This covers png, svg, or any other extension.
        $affected = User::whereNotNull('avatar_url')
            ->where('avatar_url', 'not like', 'http%')
            ->update(['avatar_url' => null]);

        // Step 2: Physically delete every file in assets/avatars.
        // We own this directory — Flarum stores all local avatars here
        // regardless of which extension saved them.
        $filesDeleted = 0;
        if (is_dir($avatarDir)) {
            foreach (scandir($avatarDir) as $filename) {
                if ($filename === '.' || $filename === '..') {
                    continue;
                }
                $filepath = $avatarDir . '/' . $filename;
                if (is_file($filepath)) {
                    unlink($filepath);
                    $filesDeleted++;
                }
            }
        }

        return new JsonResponse([
            'flushed'       => $affected,
            'filesDeleted'  => $filesDeleted,
        ]);
    }
}
