<?php
    require_once 'core/Helpers.php';

    class GroupController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }
        
        /**
         * GET /groups/{group_id} - получить данные группы по id
         */
        public function getGroupById($groupId) {
            Helpers::validateGroupId($groupId);

            $group = $this->db->fetchOne(
                "SELECT id, linkname, name, photo FROM groups WHERE id = ?",
                [$groupId]
            );
            
            if (!$group) {
                Helpers::errorResponse('Группа не найдена', 404);
            }
            
            Helpers::jsonResponse(['success' => true, 'group' => $group]);
        }
        
        /**
         * GET /groups/{linkname} - получить данные группы по linkname
         */
        public function getGroupByLinkname($linkname) {
            $group = $this->db->fetchOne(
                "SELECT id, linkname, name, photo FROM groups WHERE linkname = ?",
                [$linkname]
            );
            
            if (!$group) {
                Helpers::errorResponse('Группа не найдена', 404);
            }
            
            Helpers::jsonResponse(['success' => true, 'group' => $group]);
        }
        
        /**
         * GET /groups/list/{user_id}/{is_admin} - получаем список групп пользователя
         */
        public function getUserGroups($userId, $userIsAdmin) {
            Helpers::validateUserId($userId);
            
            try {
                $adminRoles = Helpers::getGroupAdminRoles();

                $adminRolesText = $adminRoles['text'];
                $rolesPlaceholder = $userIsAdmin ? "AND gr.name IN ($adminRolesText)" : "";

                $sql = "
                    SELECT DISTINCT 
                        g.id,
                        g.linkname,
                        g.name,
                        g.photo,
                        gm.role_id,
                        gr.name as role_name,
                        gr.title as role_title
                    FROM groups g
                    INNER JOIN group_members gm ON g.id = gm.group_id 
                    INNER JOIN group_roles gr ON gm.role_id = gr.id
                    WHERE gm.user_id = ?
                    $rolesPlaceholder
                    ORDER BY gm.joined_at DESC
                ";

                $params = [$userId];
                if ($userIsAdmin) {
                    $params = array_merge($params, $adminRoles['roles']);
                }

                $groupsList = $this->db->fetchAll($sql, $params);
                
                Helpers::jsonResponse(['success' => true, 'groupsList' => $groupsList]);

            } catch (Exception $e) {
                Helpers::errorResponse('Не удалось получить список групп', 404);
            }
        }

        /**
         * GET /groups/is-admin/{group_id}/{user_id} - проверяем является ли пользователь администратором группы
         */
        public function getUserIsAdminGroup($groupId, $userId) {
            Helpers::validateGroupId($groupId);
            Helpers::validateUserId($userId);

            
            $adminRoles = Helpers::getGroupAdminRoles();
            $adminRolesText = $adminRoles['text'];
            
            $isAdmin = $this->db->fetchOne("
                    SELECT 1
                    FROM group_members gm
                    INNER JOIN group_roles gr ON gm.role_id = gr.id
                    WHERE gm.group_id = ? AND gm.user_id = ? AND gr.name IN ($adminRolesText)
                    LIMIT 1
                ",
                array_merge([$groupId, $userId], $adminRoles['roles'])
            );

            Helpers::jsonResponse(['success' => true, 'isAdmin' => !empty($isAdmin)]);
        }
        
        /**
         * POST /groups/create - создать группу
         */
        public function createGroup() {
            $this->auth->check();
            $currentUser = $this->auth->getCurrentUser();
            
            $data = json_decode(file_get_contents('php://input'), true);
            $groupName = $data['name'];

            // Валидация входных данных
            if (empty($groupName) || strlen($groupName) < 4) {
                Helpers::errorResponse('Название группы должно содержать минимум 4 символа', 400);
                return;
            }

            try {
                $this->db->beginTransaction();

                $this->db->query(
                    "INSERT INTO groups (name, created_at) 
                    VALUES (?, NOW())",
                    [$groupName]
                );
                
                $groupId = $this->db->lastInsertId();

                $role = $this->db->fetchOne(
                    "SELECT id FROM group_roles WHERE name = ?",
                    ['owner']
                );

                if (!$role) {
                    throw new Exception('Роль "owner" не найдена');
                }

                $this->db->query(
                    "INSERT INTO group_members (group_id, user_id, role_id, joined_at) 
                    VALUES (?, ?, ?, NOW())",
                    [$groupId, $currentUser['id'], $role['id']]
                );

                $this->db->commit();
                Helpers::jsonResponse(['success' => true, 'groupId' => $groupId]);

            } catch (Exception $e) {
                $this->db->rollBack();
                Helpers::errorResponse('Ошибка создания группы: ' . $e->getMessage(), 500);
            }
        }
        
        /**
         * POST /groups/edit - редактировать группу
         */
        public function editGroup() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            
            $data = json_decode(file_get_contents('php://input'), true);
            $groupId = $data['groupId'] ?? null;
            $category = $data['category'] ?? null;
            $valueJson = $data['value'] ?? null;
            
            
            $value = json_decode($valueJson, true);
            if (!$value) {
                Helpers::errorResponse('Неверные данные', 400);
            }
            Helpers::validateGroupId($groupId);


            // Проверка админ ли группы
            $adminRoles = Helpers::getGroupAdminRoles();
            $adminRolesText = $adminRoles['text'];
            $isAdmin = $this->db->fetchOne("
                    SELECT 1
                    FROM group_members gm
                    INNER JOIN group_roles gr ON gm.role_id = gr.id
                    WHERE gm.group_id = ? AND gm.user_id = ? AND gr.name IN ($adminRolesText)
                    LIMIT 1
                ",
                array_merge([$groupId, $currentUserId], $adminRoles['roles'])
            );
            if (empty($isAdmin)) {
                Helpers::errorResponse('Нет прав на редактирование', 403);
                return;
            }
            

            switch ($category) {
                case 'base':
                    $groupName = $value['name'];
                    $groupLinkname = trim($value['linkname']);

                    // Валидация входных данных
                    if (empty($groupName) || strlen($groupName) < 4) {
                        Helpers::errorResponse('Название группы должно содержать минимум 4 символа', 400);
                        return;
                    }
                    if (empty($groupLinkname) || strlen($groupLinkname) < 4) {
                        Helpers::errorResponse('Ссылка группы должна содержать минимум 4 символа', 400);
                        return;
                    }

                    $exists = $this->db->fetchOne(
                        "SELECT id FROM groups WHERE linkname = ? AND id != ?",
                        [$groupLinkname, $groupId]
                    );
                    if ($exists) {
                        Helpers::errorResponse('Ссылка уже занята', 400);
                        return;
                    }

                    $this->db->query(
                        "UPDATE groups SET name = ?, linkname = ? 
                        WHERE id = ?",
                        [$groupName, $groupLinkname, $groupId]
                    );
                    
                    Helpers::jsonResponse(['success' => true, 'linkname' => $groupLinkname]);
                    break;
                
                default:
                    break;
            }
            
            Helpers::errorResponse('Ошибка создания группы', 500);
        }
        
        /**
         * GET /groups/members/{group_id} - получить список участников группы
         */
        public function members($groupId) {
            Helpers::validateGroupId($groupId);

            $members = $this->db->fetchAll("
                    SELECT
                        u.id,
                        u.linkname,
                        u.firstname,
                        u.photo
                    FROM group_members gm 
                    INNER JOIN users u ON gm.user_id = u.id
                    WHERE gm.group_id = ?
                ",
                [$groupId]
            );
            
            Helpers::jsonResponse(['success' => true, 'members' => $members]);
        }
        
        /**
         * GET /groups/status/subscribe/{group_id} - проверить статус подписки на группу
         */
        public function statusSubscribe($groupId) {
            $this->auth->check();
            
            Helpers::validateGroupId($groupId);

            $currentUserId = $this->auth->getCurrentUser()['id'];
            $status = $this->db->fetchOne("
                    SELECT
                        gm.user_id IS NOT NULL as isSubscribe,
                        gr.name = ? as isOwner
                    FROM group_members gm
                    LEFT JOIN group_roles gr ON gm.role_id = gr.id
                    WHERE gm.user_id = ? AND gm.group_id = ?
                    LIMIT 1
                ",
                ['owner', $currentUserId, $groupId]
            );
            
            Helpers::jsonResponse([
                'success' => true,
                'isSubscribe' => $status['isSubscribe'] ?? false,
                'isOwner' => $status['isOwner'] ?? false
            ]);
        }
        
        /**
         * POST /groups/subscribe - подписаться на группу
         */
        public function subscribe() {
            $this->auth->check();
            $currentUser = $this->auth->getCurrentUser();
            
            $data = json_decode(file_get_contents('php://input'), true);
            $groupId = $data['groupId'];

            Helpers::validateGroupId($groupId);
            
            
            try {
                $this->db->query("
                        INSERT INTO group_members (user_id, group_id, joined_at) 
                        VALUES (?, ?, NOW())
                    ",
                    [$currentUser['id'], $groupId]
                );

                Helpers::jsonResponse(['success' => true]);

            } catch (Exception $e) {
                Helpers::errorResponse('Ошибка подписки', 409);
            }
        }
        
        /**
         * POST /groups/unsubscribe - отписаться от группы
         */
        public function unsubscribe() {
            $this->auth->check();
            $currentUser = $this->auth->getCurrentUser();
            
            $data = json_decode(file_get_contents('php://input'), true);
            $groupId = $data['groupId'];

            Helpers::validateGroupId($groupId);
            
            try {
                $this->db->query("
                        DELETE FROM group_members
                        WHERE user_id = ? AND group_id = ?
                    ",
                    [$currentUser['id'], $groupId]
                );

                Helpers::jsonResponse(['success' => true]);

            } catch (Exception $e) {
                Helpers::errorResponse('Ошибка отписки', 409);
            }
        }
    }
?>
