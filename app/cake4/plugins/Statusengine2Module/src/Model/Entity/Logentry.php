<?php
declare(strict_types=1);

namespace Statusengine2Module\Model\Entity;

use Cake\ORM\Entity;

/**
 * Logentry Entity
 *
 * @property int $logentry_id
 * @property int $instance_id
 * @property \Cake\I18n\FrozenTime $logentry_time
 * @property \Cake\I18n\FrozenTime $entry_time
 * @property int $entry_time_usec
 * @property int $logentry_type
 * @property string $logentry_data
 * @property int $realtime_data
 * @property int $inferred_data_extracted
 *
 * @property \Statusengine2Module\Model\Entity\Logentry $logentry
 */
class Logentry extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'instance_id'             => true,
        'logentry_time'           => true,
        'entry_time_usec'         => true,
        'logentry_type'           => true,
        'logentry_data'           => true,
        'realtime_data'           => true,
        'inferred_data_extracted' => true,
        'logentry'                => true,
        'instance'                => true,
    ];
}