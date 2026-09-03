<?php

use App\PiniaHydrators\OptionsHydrator;
use App\PiniaHydrators\PipelineHydrator;

return [
    'prop' => '$pinia',

    'modules' => [
        'options' => OptionsHydrator::class,
        'pipeline' => PipelineHydrator::class,
    ],
];
