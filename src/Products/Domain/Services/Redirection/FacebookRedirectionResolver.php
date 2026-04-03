<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services\Redirection;

use Src\Products\Domain\ValueObjects\SimpleRedirectionModel;

final class FacebookRedirectionResolver extends AbstractSimpleRedirectionResolver
{
    protected function supportedModel(): SimpleRedirectionModel
    {
        return SimpleRedirectionModel::FACEBOOK;
    }
}
