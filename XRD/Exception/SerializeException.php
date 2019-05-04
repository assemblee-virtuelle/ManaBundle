<?php
/**
 * This file is part of the ManaBundle, a WebFinger library for Symfony
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  ManaBundle
 * @subpackage XRD
 * @author   Michel Cadennes <michel.cadennes@assemblee-virtuelle.org>
 * @license  https://opensource.org/licenses/GPL-3.0 GNU General Public License v3
 * @link https://github.com/assemblee-virtuelle/ManaBundle/tree/master/XRD/README.md
 * @version 0.1.0
 */

use AssembleeVirtuelle\XRD\ExceptionInterface;

/**
 * Exception that's thrown when saving an XRD file fails.
 */
class SerializeException extends \Exception implements ExceptionInterface
{
}
