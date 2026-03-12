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

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Extend;
use Flarum\User\Event\Registered;
use Resofire\Dicebear\Api\FlushAvatarsController;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ApiSerializer(BasicUserSerializer::class))
        ->attributes(Api\AddDicebearAvatar::class),

    (new Extend\Event())
        ->listen(Registered::class, Listener\SaveDicebearAvatarOnRegister::class),

    (new Extend\Routes('api'))
        ->post('/resofire-dicebear/flush', 'resofire-dicebear.flush', FlushAvatarsController::class),
];
