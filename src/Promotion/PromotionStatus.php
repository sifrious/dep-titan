<?php

declare(strict_types=1);

namespace Sifrious\Titan\Promotion;

enum PromotionStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Abandoned = 'abandoned';
}
