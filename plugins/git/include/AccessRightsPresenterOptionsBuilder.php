<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Git;

use GitRepository;
use ProjectUGroup;
use Git;
use User_ForgeUserGroupFactory;
use PermissionsManager;
use Project;

class AccessRightsPresenterOptionsBuilder
{

    /**
     * @var User_ForgeUserGroupFactory
     */
    private $user_group_factory;

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    public function __construct(
        User_ForgeUserGroupFactory $user_group_factory,
        PermissionsManager $permissions_manager
    ) {
        $this->user_group_factory  = $user_group_factory;
        $this->permissions_manager = $permissions_manager;
    }

    public function getOptions(Project $project, GitRepository $repository, $permission)
    {
        $user_groups     = $this->user_group_factory->getAllForProject($project);
        $options         = array();
        $selected_values = $this->permissions_manager->getAuthorizedUGroupIdsForProject(
            $project,
            $repository->getId(),
            $permission
        );

        foreach ($user_groups as $ugroup) {
            if ($ugroup->getId() == ProjectUGroup::ANONYMOUS && $permission !== Git::PERM_READ) {
                continue;
            }

            $selected  = in_array($ugroup->getId(), $selected_values) ? 'selected="selected"' : '';
            $options []= array(
                'value'    => $ugroup->getId(),
                'label'    => $ugroup->getName(),
                'selected' => $selected
            );
        }

        return $options;
    }
}