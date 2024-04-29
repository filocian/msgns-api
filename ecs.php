<?php

declare(strict_types=1);

use CodelyTv\CodingStyle;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return function (ECSConfig $ecsConfig): void {
	$ecsConfig->paths([__DIR__, ]);

	$ecsConfig->sets([CodingStyle::DEFAULT]);

	// Or this if you prefer to have the code aligned
	// $ecsConfig->sets([CodingStyle::ALIGNED]);
};
