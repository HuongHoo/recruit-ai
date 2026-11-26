<?php

namespace App\Services;

use RuntimeException;

/**
 * An AI feature could not produce a result. The message is safe to show to the user.
 */
class AiServiceException extends RuntimeException {}
