<?php

namespace App\Issues\Fixing;

/**
 * This is just a type hinting interface to be used in place of a "Fix" interface. It subclasses Fix to require the same
 * interface. Any class that returns a class that implements this interface instead of a Fix is telling the calling code
 * that it is safe to call the "fix" method and not expect any required user interaction.
 */
interface ICanFixThem extends Fixable
{

}
