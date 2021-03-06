<?php
/**
 * This file is part of the RedKiteCmsBunde Application and it is distributed
 * under the MIT License. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) RedKite Labs <webmaster@redkite-labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.redkite-labs.com
 *
 * @license    MIT License
 *
 */

namespace RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Slot\Repeated\Converter;

/**
 * Used by the Slots converter to convert a slot from its current repeated status
 * to the new one
 *
 *
 * @author RedKite Labs <webmaster@redkite-labs.com>
 */
interface SlotConverterInterface
{
    /**
     * Converts the slot's repeated status
     *
     * @api
     */
    public function convert();
}
