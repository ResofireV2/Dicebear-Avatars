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

        // Step 1: Collect the filenames we are about to clear so we know
        // exactly which files on disk belong to this extension.
        $filesToDelete = User::whereNotNull('avatar_url')
            ->where('avatar_url', 'not like', 'http%')
            ->pluck('avatar_url')
            ->toArray();

        // Step 2: Clear those avatar_url records in the database.
        $affected = User::whereNotNull('avatar_url')
            ->where('avatar_url', 'not like', 'http%')
            ->update(['avatar_url' => null]);

        // Step 3: Delete only the files we collected — nothing else.
        $filesDeleted = 0;
        foreach ($filesToDelete as $filename) {
            $filepath = $avatarDir . '/' . basename($filename);
            if (is_file($filepath)) {
                unlink($filepath);
                $filesDeleted++;
            }
        }

        return new JsonResponse([
            'flushed'      => $affected,
            'filesDeleted' => $filesDeleted,
        ]);
    }
}
