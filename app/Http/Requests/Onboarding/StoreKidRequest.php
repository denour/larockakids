<?php

namespace App\Http\Requests\Onboarding;

/**
 * Registering a brand new kid from the kiosk. The payload is identical to the edit
 * screen's, so the rules and messages live in KidFormRequest.
 */
class StoreKidRequest extends KidFormRequest {}
