<?php

namespace SwagIndustries\Melodiia\Crud\Tools;

use Symfony\Component\HttpFoundation\Request;

interface IdResolverInterface
{
    public function resolveId(Request $request, string $melodiiaModel): string;
}
