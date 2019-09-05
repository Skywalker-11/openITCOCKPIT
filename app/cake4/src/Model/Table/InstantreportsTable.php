<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use App\Lib\Traits\PaginationAndScrollIndexTrait;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\InstantreportFilter;

/**
 * Instantreports Model
 *
 * @property \App\Model\Table\ContainersTable|\Cake\ORM\Association\BelongsTo $Containers
 *
 * @method \App\Model\Entity\Instantreport get($primaryKey, $options = [])
 * @method \App\Model\Entity\Instantreport newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Instantreport[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Instantreport|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Instantreport saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Instantreport patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Instantreport[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Instantreport findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class InstantreportsTable extends Table {
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

        $this->setTable('instantreports');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsTo('Timeperiods', [
            'foreignKey' => 'timeperiod_id',
            'joinType'   => 'INNER'
        ]);

        $this->belongsToMany('Users', [
            'joinTable'    => 'instantreports_to_users',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Hostgroups', [
            'joinTable'    => 'instantreports_to_hostgroups',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Hosts', [
            'joinTable'    => 'instantreports_to_hosts',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Servicegroups', [
            'joinTable'    => 'instantreports_to_servicegroups',
            'saveStrategy' => 'replace'
        ])->setDependent(true);

        $this->belongsToMany('Services', [
            'joinTable'    => 'instantreports_to_services',
            'saveStrategy' => 'replace'
        ])->setDependent(true);
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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('container_id')
            ->requirePresence('container_id', 'create')
            ->allowEmptyString('container_id', null, false)
            ->greaterThanOrEqual('container_id', 1);

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('timeperiod_id')
            ->requirePresence('timeperiod_id', 'create')
            ->notEmptyString('timeperiod_id');

        $validator
            ->add('send_email', 'custom', [
                'rule'    => [$this, 'atLeastOneUser'],
                'message' => __('You must specify at least one user')
            ]);

        $validator
            ->add('hostgroups', 'custom', [
                'rule'    => [$this, 'atLeastOneHostgroup'],
                'message' => __('You must specify at least one host group')
            ]);

        $validator
            ->add('hosts', 'custom', [
                'rule'    => [$this, 'atLeastOneHost'],
                'message' => __('You must specify at least one host')
            ]);

        $validator
            ->add('servicegroups', 'custom', [
                'rule'    => [$this, 'atLeastOneServicegroup'],
                'message' => __('You must specify at least one service group')
            ]);

        $validator
            ->add('services', 'custom', [
                'rule'    => [$this, 'atLeastOneService'],
                'message' => __('You must specify at least one service')
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
        $rules->add($rules->existsIn(['container_id'], 'Containers'));

        return $rules;
    }

    /**
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for users if send_email is true
     */
    public function atLeastOneUser($value, $context) {
        // XNOR Operator (false and false) = true and (true and true) = true
        // if send_email true and user list is not empty, if send_mail = 1 and user list is empty
        return !(!(($context['data']['send_email'] === 1) ^ empty($context['data']['users']['_ids'])));
    }

    /**
     * @param $value
     * @param $context
     * @return int
     */
    public function atLeastOneHostgroup($value, $context) {
        return !(!(($context['data']['type'] === 1) ^ empty($context['data']['hostgroups']['_ids'])));
    }

    /**
     * @param $value
     * @param $context
     * @return int
     */
    public function atLeastOneHost($value, $context) {
        return !(!(($context['data']['type'] === 2) ^ empty($context['data']['hosts']['_ids'])));
    }

    /**
     * @param $value
     * @param $context
     * @return int
     */
    public function atLeastOneServicegroup($value, $context) {
        return !(!(($context['data']['type'] === 3) ^ empty($context['data']['servicegroups']['_ids'])));
    }

    /**
     * @param $value
     * @param $context
     * @return int
     */
    public function atLeastOneService($value, $context) {
        return !(!(($context['data']['type'] === 4) ^ empty($context['data']['services']['_ids'])));
    }

    /**
     * @param InstantreportFilter $InstantreportFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getInstantreportsIndex(InstantreportFilter $InstantreportFilter, PaginateOMat $PaginateOMat = null, $MY_RIGHTS = []) {
        $query = $this->find('all')
            ->contain([
                'Timeperiods' => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Timeperiods.id',
                            'Timeperiods.name'
                        ]);
                },
                'Users'       => function (Query $q) {
                    return $q->enableAutoFields(false)
                        ->select([
                            'Users.id',
                            'Users.firstname',
                            'Users.lastname'
                        ]);
                }
            ])
            ->select([
                'Instantreports.id',
                'Instantreports.container_id',
                'Instantreports.name',
                'Instantreports.evaluation',
                'Instantreports.type',
                'Instantreports.reflection',
                'Instantreports.downtimes',
                'Instantreports.summary',
                'Instantreports.send_email',
                'Instantreports.send_interval'
            ])
            ->group('Instantreports.id')
            ->disableHydration();

        $indexFilter = $InstantreportFilter->indexFilter();


        if (!empty($MY_RIGHTS)) {
            $indexFilter['Instantreports.container_id IN'] = $MY_RIGHTS;
        }

        $query->where($indexFilter);
        $query->order($InstantreportFilter->getOrderForPaginator('Instantreports.name', 'asc'));
        if ($PaginateOMat === null) {
            //Just execute query
            $result = $query->toArray();
        } else {
            if ($PaginateOMat->useScroll()) {
                $result = $this->scroll($query, $PaginateOMat->getHandler());
            } else {
                $result = $this->paginate($query, $PaginateOMat->getHandler(), false);
            }

        }
        return $result;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function existsById($id) {
        return $this->exists(['Instantreports.id' => $id]);
    }

    /**
     * @param $id
     * @return array
     */
    public function getInstantreportById($id) {
        $query = $this->find()
            ->where([
                'Instantreports.id' => $id
            ])
            ->first();
        return $this->formatFirstResultAsCake2($query->toArray(), false);
    }
}
