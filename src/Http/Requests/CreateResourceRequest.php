<?php

namespace Laravel\Nova\Http\Requests;

class CreateResourceRequest extends NovaRequest
{
    /**
     * Determine if this request is a create or attach request.
     */
    #[\Override]
    public function isCreateOrAttachRequest(): bool
    {
        return true;
    }
}
