<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Filter\HosttemplateFilter;

/**
 * Hosttemplates Model
 *
 * @property \App\Model\Table\CommandsTable|\Cake\ORM\Association\BelongsTo $Commands
 * @property \App\Model\Table\CommandsTable|\Cake\ORM\Association\BelongsTo $EventhandlerCommands
 * @property \App\Model\Table\TimeperiodsTable|\Cake\ORM\Association\BelongsTo $CheckPeriods
 * @property \App\Model\Table\TimeperiodsTable|\Cake\ORM\Association\BelongsTo $NotifyPeriods
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 * @property \App\Model\Table\ContactgroupsTable|\Cake\ORM\Association\HasMany $Contactgroups
 * @property \App\Model\Table\ContactsTable|\Cake\ORM\Association\HasMany $Contacts
 * @property \App\Model\Table\HostsTable|\Cake\ORM\Association\HasMany $Hosts
 * @property \App\Model\Table\HosttemplatecommandargumentvaluesTable|\Cake\ORM\Association\HasMany $Hosttemplatecommandargumentvalues
 *
 * @method \App\Model\Entity\Hosttemplate get($primaryKey, $options = [])
 * @method \App\Model\Entity\Hosttemplate newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Hosttemplate[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Hosttemplate|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Hosttemplate|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Hosttemplate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Hosttemplate[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Hosttemplate findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class HosttemplatesTable extends Table {

    use Cake2ResultTableTrait;
    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) {
        parent::initialize($config);

        $this->setTable('hosttemplates');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsToMany('Contactgroups', [
            'className'        => 'Contactgroups',
            'foreignKey'       => 'hosttemplate_id',
            'targetForeignKey' => 'contactgroup_id',
            'joinTable'        => 'contactgroups_to_hosttemplates',
            'saveStrategy'     => 'replace'
        ]);

        $this->belongsToMany('Contacts', [
            'className'        => 'Contacts',
            'foreignKey'       => 'hosttemplate_id',
            'targetForeignKey' => 'contact_id',
            'joinTable'        => 'contacts_to_hosttemplates',
            'saveStrategy'     => 'replace'
        ]);

        $this->belongsToMany('Hostgroups', [
            'className'        => 'Hostgroups',
            'foreignKey'       => 'hosttemplate_id',
            'targetForeignKey' => 'hostgroup_id',
            'joinTable'        => 'hosttemplates_to_hostgroups',
            'saveStrategy'     => 'replace'
        ]);

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsTo('CheckPeriod', [
            'className'  => 'Timeperiods',
            'foreignKey' => 'check_period_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsTo('NotifyPeriod', [
            'className'  => 'Timeperiods',
            'foreignKey' => 'notify_period_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsTo('CheckCommand', [
            'className'  => 'Commands',
            'foreignKey' => 'command_id',
            'joinType'   => 'INNER'
        ]);

        $this->hasMany('Customvariables', [
            'conditions'   => [
                'objecttype_id' => OBJECT_HOSTTEMPLATE
            ],
            'foreignKey'   => 'object_id',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->hasMany('Hosttemplatecommandargumentvalues', [
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->hasMany('Host', [
            'saveStrategy' => 'replace'
        ]);

    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator) {
        $validator
            ->integer('id')
            ->allowEmptyString('id', 'create');

        $validator
            ->scalar('uuid')
            ->maxLength('uuid', 37)
            ->requirePresence('uuid', 'create')
            ->allowEmptyString('uuid', false)
            ->add('uuid', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->allowEmptyString('name', false);

        $validator
            ->allowEmptyString('description', true);

        $validator
            ->integer('priority')
            ->requirePresence('priority', 'create')
            ->range('priority', [1, 5], __('This value must be between 1 and 5'));

        $validator
            ->integer('container_id')
            ->requirePresence('container_id', 'create')
            ->allowEmptyString('container_id', false)
            ->greaterThanOrEqual('container_id', 1);

        $validator
            ->integer('max_check_attempts')
            ->requirePresence('max_check_attempts', 'create')
            ->greaterThanOrEqual('max_check_attempts', 1, __('This value need to be at least 1'))
            ->allowEmptyString('max_check_attempts', false);

        $validator
            ->numeric('notification_interval')
            ->requirePresence('notification_interval', 'create')
            ->greaterThanOrEqual('notification_interval', 0, __('This value need to be at least 0'))
            ->allowEmptyString('notification_interval', false);

        $validator
            ->integer('check_interval')
            ->requirePresence('check_interval', 'create')
            ->greaterThanOrEqual('check_interval', 1, __('This value need to be at least 1'))
            ->allowEmptyString('check_interval', false);

        $validator
            ->integer('retry_interval')
            ->requirePresence('retry_interval', 'create')
            ->greaterThanOrEqual('retry_interval', 1, __('This value need to be at least 1'))
            ->allowEmptyString('retry_interval', false);

        $validator
            ->integer('check_period_id')
            ->requirePresence('check_period_id', 'create')
            ->greaterThan('check_period_id', 0, __('Please select a check period'))
            ->allowEmptyString('check_period_id', false);

        $validator
            ->integer('command_id')
            ->requirePresence('command_id', 'create')
            ->greaterThan('command_id', 0, __('Please select a check command'))
            ->allowEmptyString('command_id', false);

        $validator
            ->integer('notify_period_id')
            ->requirePresence('notify_period_id', 'create')
            ->greaterThan('notify_period_id', 0, __('Please select a notify period'))
            ->allowEmptyString('notify_period_id', false);

        $validator
            ->boolean('notify_on_recovery')
            ->requirePresence('notify_on_recovery', 'create')
            ->allowEmptyString('notify_on_recovery', false)
            ->add('notify_on_recovery', 'custom', [
                'rule'    => [$this, 'checkNotificationOptions'],
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_down')
            ->requirePresence('notify_on_down', 'create')
            ->allowEmptyString('notify_on_down', false)
            ->add('notify_on_down', 'custom', [
                'rule'    => [$this, 'checkNotificationOptions'],
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_unreachable')
            ->requirePresence('notify_on_unreachable', 'create')
            ->allowEmptyString('notify_on_unreachable', false)
            ->add('notify_on_unreachable', 'custom', [
                'rule'    => [$this, 'checkNotificationOptions'],
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_flapping')
            ->requirePresence('notify_on_flapping', 'create')
            ->allowEmptyString('notify_on_flapping', false)
            ->add('notify_on_flapping', 'custom', [
                'rule'    => [$this, 'checkNotificationOptions'],
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('notify_on_downtime')
            ->requirePresence('notify_on_downtime', 'create')
            ->allowEmptyString('notify_on_downtime', false)
            ->add('notify_on_downtime', 'custom', [
                'rule'    => [$this, 'checkNotificationOptions'],
                'message' => __('You must specify at least one notification option.')
            ]);

        $validator
            ->boolean('flap_detection_enabled')
            ->requirePresence('flap_detection_enabled', 'create')
            ->allowEmptyString('flap_detection_enabled', false);

        $validator
            ->boolean('flap_detection_on_up')
            ->requirePresence('flap_detection_on_up', 'create')
            ->allowEmptyString('flap_detection_on_up', false)
            ->add('flap_detection_on_up', 'custom', [
                'rule'    => [$this, 'checkFlapDetectionOptions'],
                'message' => __('You must specify at least one flap detection option.')
            ]);

        $validator
            ->boolean('flap_detection_on_down')
            ->requirePresence('flap_detection_on_down', 'create')
            ->allowEmptyString('flap_detection_on_down', false)
            ->add('flap_detection_on_down', 'custom', [
                'rule'    => [$this, 'checkFlapDetectionOptions'],
                'message' => __('You must specify at least one flap detection option.')
            ]);

        $validator
            ->boolean('flap_detection_on_unreachable')
            ->requirePresence('flap_detection_on_unreachable', 'create')
            ->allowEmptyString('flap_detection_on_unreachable', false)
            ->add('flap_detection_on_unreachable', 'custom', [
                'rule'    => [$this, 'checkFlapDetectionOptions'],
                'message' => __('You must specify at least one flap detection option.')
            ]);

        $validator
            ->numeric('low_flap_threshold')
            ->requirePresence('low_flap_threshold', 'create')
            ->allowEmptyString('low_flap_threshold', false);

        $validator
            ->numeric('high_flap_threshold')
            ->requirePresence('high_flap_threshold', 'create')
            ->allowEmptyString('high_flap_threshold', false);

        $validator
            ->boolean('process_performance_data')
            ->requirePresence('process_performance_data', false)
            ->allowEmptyString('process_performance_data', true);

        $validator
            ->boolean('freshness_checks_enabled')
            ->requirePresence('freshness_checks_enabled', false)
            ->allowEmptyString('freshness_checks_enabled', true);

        $validator
            ->integer('freshness_threshold')
            ->allowEmptyString('freshness_threshold');

        $validator
            ->boolean('passive_checks_enabled')
            ->requirePresence('passive_checks_enabled', 'create')
            ->allowEmptyString('passive_checks_enabled', false);

        $validator
            ->boolean('event_handler_enabled')
            ->requirePresence('event_handler_enabled', 'create')
            ->allowEmptyString('event_handler_enabled', false);

        $validator
            ->boolean('active_checks_enabled')
            ->requirePresence('active_checks_enabled', 'create')
            ->allowEmptyString('active_checks_enabled', false);

        $validator
            ->scalar('notes')
            ->requirePresence('notes', false)
            ->allowEmptyString('notes', true)
            ->maxLength('notes', 255);

        $validator
            ->scalar('tags')
            ->requirePresence('tags', false)
            ->allowEmptyString('tags', true)
            ->maxLength('tags', 255);

        $validator
            ->scalar('host_url')
            ->requirePresence('host_url', false)
            ->allowEmptyString('host_url', true)
            ->maxLength('host_url', 255);

        $validator
            ->add('contacts', 'custom', [
                'rule'    => [$this, 'atLeastOne'],
                'message' => __('You must specify at least one contact or contact group.')
            ]);

        $validator
            ->add('contactgroups', 'custom', [
                'rule'    => [$this, 'atLeastOne'],
                'message' => __('You must specify at least one contact or contact group.')
            ]);

        $validator
            ->allowEmptyString('customvariables', true)
            ->add('customvariables', 'custom', [
                'rule'    => [$this, 'checkMacroNames'],
                'message' => _('Macro name needs to be unique')
            ]);


        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules) {
        $rules->add($rules->isUnique(['uuid']));

        return $rules;
    }

    /**
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for contacts and or contact groups
     */
    public function atLeastOne($value, $context) {
        return !empty($context['data']['contacts']['_ids']) || !empty($context['data']['contactgroups']['_ids']);
    }

    /**
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for contacts and or contact groups
     */
    public function checkNotificationOptions($value, $context) {
        $notificationOptions = [
            'notify_on_recovery',
            'notify_on_down',
            'notify_on_unreachable',
            'notify_on_flapping',
            'notify_on_downtime'
        ];

        foreach ($notificationOptions as $notificationOption) {
            if (isset($context['data'][$notificationOption]) && $context['data'][$notificationOption] == 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for contacts and or contact groups
     */
    public function checkFlapDetectionOptions($value, $context) {
        $flapDetectionOptions = [
            'flap_detection_on_up',
            'flap_detection_on_down',
            'flap_detection_on_unreachable'
        ];

        if (!isset($context['data']['flap_detection_enabled']) || $context['data']['flap_detection_enabled'] == 0) {
            return true;
        }

        foreach ($flapDetectionOptions as $flapDetectionOption) {
            if (isset($context['data'][$flapDetectionOption]) && $context['data'][$flapDetectionOption] == 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for contacts and or contact groups
     */
    public function checkMacroNames($value, $context) {
        if (isset($context['data']['customvariables']) && is_array($context['data']['customvariables'])) {
            $usedNames = [];

            foreach ($context['data']['customvariables'] as $macro) {
                if (in_array($macro['name'], $usedNames, true)) {
                    //Macro name not unique
                    return false;
                }
                $usedNames[] = $macro['name'];
            }
        }

        return true;
    }

    /**
     * @param HosttemplateFilter $CommandsFilter
     * @param null $PaginateOMat
     * @return array
     */
    public function getHosttemplatesIndex(HosttemplateFilter $HosttemplateFilter, $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all')->disableHydration();
        $where = $HosttemplateFilter->indexFilter();
        $where['Hosttemplates.hosttemplatetype_id'] = GENERIC_HOSTTEMPLATE;
        if (!empty($MY_RIGHTS)) {
            $where['Hosttemplates.container_id IN'] = $MY_RIGHTS;
        }

        $query->where($where);
        $query->order($HosttemplateFilter->getOrderForPaginator('Hosttemplates.name', 'asc'));

        if ($PaginateOMat === null) {
            //Just execute query
            $result = $this->formatResultAsCake2($query->toArray(), false);
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler(), false);
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }
        }

        return $result;
    }

    /**
     * @param int $id
     * @param array $contain
     * @return array
     */
    public function getHosttemplateById($id, $contain = ['Containers']) {
        $query = $this->find()
            ->where([
                'Hosttemplates.id' => $id
            ])
            ->contain($contain)
            ->disableHydration()
            ->first();

        return $this->formatFirstResultAsCake2($query, true);
    }

    /**
     * @param int $id
     * @return array
     */
    public function getHosttemplateForEdit($id) {
        $query = $this->find()
            ->where([
                'Hosttemplates.id' => $id
            ])
            ->contain([
                'Contactgroups',
                'Contacts',
                'Hostgroups',
                'Customvariables',
                'Hosttemplatecommandargumentvalues' => [
                    'Commandarguments'
                ]
            ])
            ->disableHydration()
            ->first();

        $hosttemplate = $query;
        $hosttemplate['hostgroups'] = [
            '_ids' => Hash::extract($query, 'hostgroups.{n}.id')
        ];
        $hosttemplate['contacts'] = [
            '_ids' => Hash::extract($query, 'contacts.{n}.id')
        ];
        $hosttemplate['contactgroups'] = [
            '_ids' => Hash::extract($query, 'contactgroups.{n}.id')
        ];

        return [
            'Hosttemplate' => $hosttemplate
        ];
    }

    /**
     * @param int $id
     * @return int
     */
    public function getContainerIdById($id) {
        $query = $this->find()
            ->select([
                'Hosttemplates.id',
                'Hosttemplates.container_id'
            ])
            ->where([
                'Hosttemplates.id' => $id
            ])
            ->firstOrFail();

        return (int)$query->get('container_id');
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getHosttemplatesForCopy($ids = []) {

        $query = $this->find()
            ->select([
                'Hosttemplates.id',
                'Hosttemplates.name',
                'Hosttemplates.description',
                'Hosttemplates.command_id',
                'Hosttemplates.active_checks_enabled'
            ])
            ->contain([
                'Hosttemplatecommandargumentvalues' => [
                    'Commandarguments'
                ]
            ])
            ->where(['Hosttemplates.id IN' => $ids])
            ->order(['Hosttemplates.id' => 'asc'])
            ->disableHydration()
            ->all();

        $query = $query->toArray();

        if ($query === null) {
            return [];
        }

        return $query;
    }

    /**
     * @param array $dataToParse
     * @return array
     */
    public function resolveDataForChangelog($dataToParse = []) {
        $extDataForChangelog = [
            'Contact' => [],
            'Contactgroup',
            'CheckPeriod',
            'NotifyPeriod',
            'CheckCommand',

        ];

        /** @var $CommandsTable CommandsTable */
        $CommandsTable = TableRegistry::getTableLocator()->get('Commands');
        /** @var $ContactsTable ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        /** @var $ContactgroupsTable ContactgroupsTable */
        $ContactgroupsTable = TableRegistry::getTableLocator()->get('Contactgroups');
        /** @var $TimeperiodsTable TimeperiodsTable */
        $TimeperiodsTable = TableRegistry::getTableLocator()->get('Timeperiods');

        if (!empty($dataToParse['Hosttemplate']['contacts']['_ids'])) {
            foreach ($ContactsTable->getContactsAsList($dataToParse['Hosttemplate']['contacts']['_ids']) as $contactId => $contactName) {
                $extDataForChangelog['Contact'][] = [
                    'id'   => $contactId,
                    'name' => $contactName
                ];
            }
        }

        if (!empty($dataToParse['Hosttemplate']['contactgroups']['_ids'])) {
            foreach ($ContactgroupsTable->getContactgroupsAsList($dataToParse['Hosttemplate']['contactgroups']['_ids']) as $contactgroupId => $contactgroupName) {
                $extDataForChangelog['Contactgroup'][] = [
                    'id'   => $contactgroupId,
                    'name' => $contactgroupName
                ];
            }
        }

        if (!empty($dataToParse['Hosttemplate']['check_period_id'])) {
            foreach ($TimeperiodsTable->getTimeperiodsAsList($dataToParse['Hosttemplate']['check_period_id']) as $timeperiodId => $timeperiodName) {
                $extDataForChangelog['CheckPeriod'][] = [
                    'id'   => $timeperiodId,
                    'name' => $timeperiodName
                ];
            }
        }

        if (!empty($dataToParse['Hosttemplate']['notify_period_id'])) {
            foreach ($TimeperiodsTable->getTimeperiodsAsList($dataToParse['Hosttemplate']['notify_period_id']) as $timeperiodId => $timeperiodName) {
                $extDataForChangelog['NotifyPeriod'][] = [
                    'id'   => $timeperiodId,
                    'name' => $timeperiodName
                ];
            }
        }

        if (!empty($dataToParse['Hosttemplate']['command_id'])) {
            foreach ($CommandsTable->getCommandByIdAsList($dataToParse['Hosttemplate']['command_id']) as $commandId => $commandName) {
                $extDataForChangelog['CheckCommand'][] = [
                    'id'   => $commandId,
                    'name' => $commandName
                ];
            }
        }

        return $extDataForChangelog;
    }

    /**
     * @param int $hosttemplateId
     * @return bool
     */
    public function allowDelete($hosttemplateId) {
        /** @var $HostsTable HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        $count = $HostsTable->find()
            ->where([
                'Hosts.hosttemplate_id' => $hosttemplateId
            ])
            ->count();

        return $count === 0;
    }

    /**
     * @param int $timeperiodId
     * @return bool
     */
    public function isTimeperiodUsedByHosttemplate($timeperiodId) {
        $count = $this->find()
            ->where([
                'OR' => [
                    'Hosttemplates.check_period_id'  => $timeperiodId,
                    'Hosttemplates.notify_period_id' => $timeperiodId
                ]
            ])->count();

        if ($count > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Hosttemplates.id' => $id]);
    }

}
