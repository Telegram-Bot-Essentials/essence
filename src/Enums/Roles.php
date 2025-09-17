<?php

namespace TelegramBotEssentials\Essence\Enums;

enum Roles: int
{
    case ADMIN = 100;
    case MODERATOR = 10;
    case MEMBER = 0;
}
