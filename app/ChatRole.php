<?php

namespace App;

enum ChatRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
