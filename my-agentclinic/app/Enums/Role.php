<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Therapist = 'therapist';
    case Agent = 'agent';
}
