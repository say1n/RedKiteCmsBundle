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

/**
 * BlockManagerFactory is the object responsible to create a new BlockManager object
 *
 * BlockManagers are created by an existing Block object or by a valid string that identifies
 * a valid BlockType
 *
 * @author RedKite Labs <webmaster@redkite-labs.com>
 */
namespace RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Block;

use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Model\Block;
use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Repository\Factory\FactoryRepositoryInterface;
use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\EventsHandler\EventsHandlerInterface;

/**
 * BlockManagerFactory is the object responsible to create a new BlockManager object
 *
 * BlockManagerFactory collects all the BlockManager objects and uses the to create
 * the new object from an existing Block object or by a valid string that identifies
 * a valid BlockType.
 *
 * @author RedKite Labs <webmaster@redkite-labs.com>
 *
 * @api
 */
class BlockManagerFactory implements BlockManagerFactoryInterface
{
    /**
     * The generable blockManagers
     *
     * @var array $blockManagersItems
     *
     * @api
     */
    private $blockManagersItems = array();

    /**
     * @var \RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Repository\Factory\FactoryRepositoryInterface
     *
     * @api
     */
    private $factoryRepository;

    /**
     * @var \RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\EventsHandler\EventsHandlerInterface
     *
     * @api
     */
    private $eventsHandler;

    /**
     * Constructor
     *
     * @param \RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\EventsHandler\EventsHandlerInterface          $eventsHandler
     * @param \RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Repository\Factory\FactoryRepositoryInterface $factoryRepository
     *
     * @api
     */
    public function __construct(EventsHandlerInterface $eventsHandler, FactoryRepositoryInterface $factoryRepository = null)
    {
        $this->eventsHandler = $eventsHandler;
        $this->factoryRepository = $factoryRepository;
    }

    /**
     * Adds a block manager base object.
     *
     * This method is usually called by the BlocksCompilerPass object
     *
     * @param BlockManagerInterface $blockManager
     * @param array                   $attributes
     *
     * @api
     */
    public function addBlockManager(BlockManagerInterface $blockManager, array $attributes)
    {
        if (empty($attributes['type'])) {
            return;
        }

        $blockManager->setFactoryRepository($this->factoryRepository);
        $this->blockManagersItems[] = new BlockManagerFactoryItem($blockManager, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockManager($block)
    {
        $isBlock = $block instanceof Block;
        $blockType = $isBlock ? $block->getType() : $block;

        $items = count($this->blockManagersItems);
        if ($items == 0) {
            return null;
        }

        foreach ($this->blockManagersItems as $blockManagerItem) {
            if ($blockManagerItem->getType() == $blockType) {
                $blockManager = $blockManagerItem->getBlockManager();
                $blockManager = clone($blockManager);
                $blockManager->setEventsHandler($this->eventsHandler);
                if ($isBlock) $blockManager->set($block);
                return $blockManager;
            }
        }

        return null;
    }

    public function getAvailableBlocks()
    {
        $blockManagers = array();
        foreach ($this->blockManagersItems as $blockManagerItem) {
            if ($blockManagerItem->getBlockManager()->getIsInternalBlock()) {
                continue;
            }

            $blockManagers[] = $blockManagerItem->getType();
        }

        return $blockManagers;
    }

    /**
     * Returns an array that contains the blocks description objects that can be created by the
     * factory, ordered by group
     *
     * @return array
     *
     * @api
     */
    public function getBlocks()
    {
        $ungroupedKey = 'Ungrouped';
        $blockGroups = array();
        foreach ($this->blockManagersItems as $blockManagerItem) {

            if ($blockManagerItem->getBlockManager()->getIsInternalBlock()) {
                continue;
            }

            $groups = array($ungroupedKey);
            $group = $blockManagerItem->getGroup();
            if ($group != "") {
                $groups = explode(",", $group);
                if ($group != "redkitecms_internals" && count($groups) == 1) {
                    $groups = array($ungroupedKey);
                }
            }

            $blockGroup = array($blockManagerItem->getType() => array('description' => $blockManagerItem->getDescription(), 'filter' => $blockManagerItem->getFilter()));
            foreach (array_reverse($groups) as $key) {
               $blockGroup = array(trim($key) => $blockGroup);
            }
            $blockGroups = array_merge_recursive($blockGroups, $blockGroup);
        }

        // First displayed group
        $redKiteBlocks = array("Default" => $this->extractGroup('redkitecms_internals', $blockGroups));
        // Last displayed group
        $notGrouped = $this->extractGroup($ungroupedKey, $blockGroups);
        // Sorts
        $this->recurKsort($redKiteBlocks);
        if (count($notGrouped) > 0) {
            $this->recurKsort($notGrouped);
        }

        // Exstracts and sorts all other groups
        $blocks = array();
        foreach ($blockGroups as $blockGroup) {
            $blocks = array_merge($blocks, $blockGroup);
        }
        $this->recurKsort($blocks);

        // Merges blocks
        $blocks = array_merge($redKiteBlocks, $blocks);
        if ( ! empty($notGrouped)) {
            $blocks = array_merge($blocks, array($ungroupedKey => $notGrouped));
        }

        return $blocks;
    }

    private function extractGroup($group, &$groups)
    {
        if (!array_key_exists($group, $groups)) {
            return array();
        }

        $blocks = $groups[$group];
        if (!empty($blocks)) {
            unset($groups[$group]);
        }

        return $blocks;
    }

    private function recurKsort(&$array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) $this->recurKsort($value);
        }

        return ksort($array);
    }
}
