<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.
?>

<div class="row">
    <div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
        <h1 class="page-title txt-color-blueDark">
            <i class="fa fa-power-off fa-fw "></i>
            <?php echo __('Downtimes'); ?>
            <span>>
                <?php echo __('Services'); ?>
                > <?php echo __('Overview'); ?>
            </span>
        </h1>
    </div>
</div>

<div id="error_msg"></div>
<div class="row">
    <div class="col-xs-12">
        <div class="alert alert-success alert-block" ng-show="showFlashSuccess">
            <a href="#" data-dismiss="alert" class="close">×</a>
            <h4 class="alert-heading"><i
                        class="fa fa-check-circle-o"></i> <?php echo __('Command sent successfully'); ?>
            </h4>
            <?php echo __('Data refresh in'); ?> {{ autoRefreshCounter
            }} <?php echo __('seconds...'); ?>
        </div>
    </div>
</div>

<mass-delete-service-downtimes callback="showServiceDowntimeFlashMsg"></mass-delete-service-downtimes>

<section id="widget-grid" class="">

    <div class="row">

        <article class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="jarviswidget jarviswidget-color-blueDark" id="wid-id-1" data-widget-editbutton="false">
                <header>
                    <div class="widget-toolbar" role="menu">
                        <button type="button" class="btn btn-xs btn-default" ng-click="load()">
                            <i class="fa fa-refresh"></i>
                            <?php echo __('Refresh'); ?>
                        </button>

                        <?php echo $this->element('Downtimes/create_dropdown'); ?>

                        <button type="button" class="btn btn-xs btn-primary" ng-click="triggerFilter()">
                            <i class="fa fa-filter"></i>
                            <?php echo __('Filter'); ?>
                        </button>
                    </div>


                    <div class="jarviswidget-ctrls" role="menu"></div>

                    <span class="widget-icon hidden-mobile"> <i class="fa fa-power-off"></i> </span>
                    <h2 class="hidden-mobile"><?php echo __('Service downtimes overview'); ?> </h2>
                    <?php echo $this->element('Downtimes/tabs'); ?>
                </header>
                <div>

                    <div class="widget-body no-padding">

                        <div class="list-filter well" ng-show="showFilter">
                            <h3><i class="fa fa-filter"></i> <?php echo __('Filter'); ?></h3>
                            <div class="row">
                                <div class="col-xs-12 col-md-6">
                                    <div class="form-group smart-form">
                                        <label class="input"> <i class="icon-prepend"
                                                                 style="padding-right:14px;"><?php echo __('From'); ?></i>
                                            <input type="text" class="input-sm" style="padding-left:50px;"
                                                   placeholder="<?php echo __('From Date'); ?>"
                                                   ng-model="filter.from"
                                                   ng-model-options="{debounce: 500}">
                                        </label>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6">
                                    <div class="form-group smart-form">
                                        <label class="input"> <i class="icon-prepend fa fa-user"></i>
                                            <input type="text" class="input-sm"
                                                   placeholder="<?php echo __('Filter by user'); ?>"
                                                   ng-model="filter.DowntimeService.author_name"
                                                   ng-model-options="{debounce: 500}">
                                        </label>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-md-6">
                                    <div class="form-group smart-form">
                                        <label class="input"> <i class="icon-prepend"
                                                                 style="padding-right:14px;"><?php echo __('To'); ?></i>
                                            <input type="text" class="input-sm" style="padding-left:50px;"
                                                   placeholder="<?php echo __('To Date'); ?>"
                                                   ng-model="filter.to"
                                                   ng-model-options="{debounce: 500}">
                                        </label>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-md-6">
                                    <div class="form-group smart-form">
                                        <label class="input"> <i class="icon-prepend fa fa-filter"></i>
                                            <input type="text" class="input-sm"
                                                   placeholder="<?php echo __('Filter by comment'); ?>"
                                                   ng-model="filter.DowntimeService.comment_data"
                                                   ng-model-options="{debounce: 500}">
                                        </label>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-md-6">
                                    <div class="form-group smart-form">
                                        <label class="input"> <i class="icon-prepend fa fa-desktop"></i>
                                            <input type="text" class="input-sm"
                                                   placeholder="<?php echo __('Filter by host name'); ?>"
                                                   ng-model="filter.Host.name"
                                                   ng-model-options="{debounce: 500}">
                                        </label>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6">
                                    <div class="form-group smart-form">
                                        <label class="input"> <i class="icon-prepend fa fa-cog"></i>
                                            <input type="text" class="input-sm"
                                                   placeholder="<?php echo __('Filter by service name'); ?>"
                                                   ng-model="filter.Service.name"
                                                   ng-model-options="{debounce: 500}">
                                        </label>
                                    </div>
                                </div>

                            </div>
                            <div class="row">

                                <div class="col-xs-12 col-md-3">
                                    <fieldset>
                                        <legend><?php echo __('Options'); ?></legend>
                                        <div class="form-group smart-form">
                                            <label class="checkbox small-checkbox-label">
                                                <input type="checkbox" name="checkbox" checked="checked"
                                                       ng-model="filter.isRunning"
                                                       ng-model-options="{debounce: 500}">
                                                <i class="checkbox-primary"></i>
                                                <?php echo __('Is running'); ?>
                                            </label>

                                            <label class="checkbox small-checkbox-label">
                                                <input type="checkbox" name="checkbox" checked="checked"
                                                       ng-model="filter.DowntimeService.was_not_cancelled"
                                                       ng-model-options="{debounce: 500}">
                                                <i class="checkbox-primary"></i>
                                                <?php echo __('Was not cancelled'); ?>
                                            </label>

                                            <label class="checkbox small-checkbox-label">
                                                <input type="checkbox" name="checkbox" checked="checked"
                                                       ng-model="filter.DowntimeService.was_cancelled"
                                                       ng-model-options="{debounce: 500}">
                                                <i class="checkbox-primary"></i>
                                                <?php echo __('Was cancelled'); ?>
                                            </label>

                                            <label class="checkbox small-checkbox-label">
                                                <input type="checkbox" name="checkbox" checked="checked"
                                                       ng-model="filter.hideExpired"
                                                       ng-model-options="{debounce: 500}">
                                                <i class="checkbox-primary"></i>
                                                <?php echo __('Hide expired'); ?>
                                            </label>
                                        </div>
                                    </fieldset>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="pull-right margin-top-10">
                                        <button type="button" ng-click="resetFilter()"
                                                class="btn btn-xs btn-danger">
                                            <?php echo __('Reset Filter'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mobile_table">
                            <table id="servicedowntimes_list"
                                   class="table table-striped table-hover table-bordered smart-form" style="">
                                <thead>
                                <tr>
                                    <th class="no-sort text-center"><i class="fa fa-check-square-o fa-lg"></i></th>
                                    <th class="no-sort"><?php echo __('Running'); ?></th>
                                    <th class="no-sort" ng-click="orderBy('Host.name')">
                                        <i class="fa" ng-class="getSortClass('Host.name')"></i>
                                        <?php echo __('Host'); ?>
                                    </th>
                                    <th class="no-sort" ng-click="orderBy('Service.name')">
                                        <i class="fa" ng-class="getSortClass('Service.name')"></i>
                                        <?php echo __('Service'); ?>
                                    </th>
                                    <th class="no-sort" ng-click="orderBy('DowntimeService.author_name')">
                                        <i class="fa" ng-class="getSortClass('DowntimeService.author_name')"></i>
                                        <?php echo __('User'); ?>
                                    </th>
                                    <th class="no-sort" ng-click="orderBy('DowntimeService.comment_data')">
                                        <i class="fa" ng-class="getSortClass('DowntimeService.comment_data')"></i>
                                        <?php echo __('Comment'); ?>
                                    </th>
                                    <th class="no-sort" ng-click="orderBy('DowntimeService.entry_time')">
                                        <i class="fa" ng-class="getSortClass('DowntimeService.entry_time')"></i>
                                        <?php echo __('Created'); ?>
                                    </th>
                                    <th class="no-sort" ng-click="orderBy('DowntimeService.scheduled_start_time')">
                                        <i class="fa"
                                           ng-class="getSortClass('DowntimeService.scheduled_start_time')"></i>
                                        <?php echo __('Start'); ?>
                                    </th>
                                    <th class="no-sort" ng-click="orderBy('DowntimeService.scheduled_end_time')">
                                        <i class="fa" ng-class="getSortClass('DowntimeService.scheduled_end_time')"></i>
                                        <?php echo __('End'); ?>
                                    </th>
                                    <th class="no-sort" ng-click="orderBy('DowntimeService.duration')">
                                        <i class="fa" ng-class="getSortClass('DowntimeService.duration')"></i>
                                        <?php echo __('Duration'); ?>
                                    </th>
                                    <th class="no-sort" ng-click="orderBy('DowntimeService.was_cancelled')">
                                        <i class="fa" ng-class="getSortClass('DowntimeService.was_cancelled')"></i>
                                        <?php echo __('Was cancelled'); ?>
                                    </th>
                                    <th class="no-sort"><?php echo __('Delete'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr ng-repeat="downtime in downtimes">
                                    <td class="width-5">
                                        <input type="checkbox"
                                               ng-model="massChange[downtime.DowntimeService.internalDowntimeId]"
                                               ng-show="downtime.DowntimeService.allowEdit && downtime.DowntimeService.isCancellable">
                                    </td>

                                    <td class="text-center">
                                        <downtimeicon downtime="downtime.DowntimeService"></downtimeicon>
                                    </td>

                                    <td>
                                        <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                            <a href="/hosts/browser/{{ downtime.Host.id }}">
                                                {{ downtime.Host.hostname }}
                                            </a>
                                        <?php else: ?>
                                            {{ downtime.Host.hostname }}
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($this->Acl->hasPermission('browser', 'services')): ?>
                                            <a href="/services/browser/{{ downtime.Service.id }}">
                                                {{ downtime.Service.servicename }}
                                            </a>
                                        <?php else: ?>
                                            {{ downtime.Service.servicename }}
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        {{downtime.DowntimeService.authorName}}
                                    </td>

                                    <td>
                                        {{downtime.DowntimeService.commentData}}
                                    </td>

                                    <td>
                                        {{downtime.DowntimeService.entryTime}}
                                    </td>

                                    <td>
                                        {{downtime.DowntimeService.scheduledStartTime}}
                                    </td>

                                    <td>
                                        {{downtime.DowntimeService.scheduledEndTime}}
                                    </td>

                                    <td>
                                        {{downtime.DowntimeService.durationHuman}}
                                    </td>

                                    <td>
                                        <span ng-if="downtime.DowntimeService.wasCancelled"><?php echo __('Yes'); ?></span>
                                        <span ng-if="!downtime.DowntimeService.wasCancelled"><?php echo __('No'); ?></span>
                                    </td>

                                    <td>
                                        <?php if ($this->Acl->hasPermission('delete', 'downtimes')): ?>
                                            <button
                                                    class="btn btn-xs btn-danger"
                                                    ng-if="downtime.DowntimeService.allowEdit && downtime.DowntimeService.isCancellable"
                                                    ng-click="confirmServiceDowntimeDelete(getObjectForDelete(downtime))">
                                                <i class="fa fa-trash-o"></i> <?php echo __('Delete'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>

                                </tr>

                                <tr>
                                </tbody>
                            </table>
                            <div class="row margin-top-10 margin-bottom-10">
                                <div class="row margin-top-10 margin-bottom-10" ng-show="downtimes.length == 0">
                                    <div class="col-xs-12 text-center txt-color-red italic">
                                        <?php echo __('No entries match the selection'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="row margin-top-10 margin-bottom-10">
                                <div class="col-xs-12 col-md-2 text-muted text-center">
                                    <span ng-show="selectedElements > 0">({{selectedElements}})</span>
                                </div>
                                <div class="col-xs-12 col-md-2">
                                <span ng-click="selectAll()" class="pointer">
                                    <i class="fa fa-lg fa-check-square-o"></i>
                                    <?php echo __('Select all'); ?>
                                </span>
                                </div>
                                <div class="col-xs-12 col-md-2">
                                <span ng-click="undoSelection()" class="pointer">
                                    <i class="fa fa-lg fa-square-o"></i>
                                    <?php echo __('Undo selection'); ?>
                                </span>
                                </div>
                                <div class="col-xs-12 col-md-2 txt-color-red">
                                <span ng-click="confirmServiceDowntimeDelete(getObjectsForDelete())" class="pointer">
                                    <i class="fa fa-lg fa-trash-o"></i>
                                    <?php echo __('Delete'); ?>
                                </span>
                                </div>
                            </div>

                            <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                            <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                            <?php echo $this->element('paginator_or_scroll'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </div>
</section>
