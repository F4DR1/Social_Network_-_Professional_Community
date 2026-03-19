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
        public function getGroupById($id) {
            $group = $this->db->fetchOne(
                "SELECT id, linkname, name, photo FROM groups WHERE id = ?",
                [$id]
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
            if (!is_numeric($userId) || $userId <= 0) {
                Helpers::errorResponse('Неверный ID пользователя', 400);
            }

            try {
                $adminRoles = Helpers::getGroupAdminRoles();

                $adminRolesText = implode(',', array_fill(0, count($adminRoles), '?'));
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
                    $params = array_merge($params, $adminRoles);
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
            if (!is_numeric($groupId) || $groupId <= 0) {
                Helpers::errorResponse('Неверный ID группы', 400);
            }
            if (!is_numeric($userId) || $userId <= 0) {
                Helpers::errorResponse('Неверный ID пользователя', 400);
            }

            
            $adminRoles = Helpers::getGroupAdminRoles();

            $adminRolesText = implode(',', array_fill(0, count($adminRoles), '?'));
            
            $sql = "
                SELECT 1 
                FROM group_members gm 
                INNER JOIN group_roles gr ON gm.role_id = gr.id
                WHERE gm.group_id = ?
                AND gm.user_id = ?
                AND gr.name IN ($adminRolesText)
                LIMIT 1
            ";

            $params = [$groupId, $userId];
            $params = array_merge($params, $adminRoles);
        
            $isAdmin = $this->db->fetchOne($sql, $params);

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
         * POST /groups/create/{group_id} - редактировать группу
         */
        public function editGroup($groupId) {
            $this->auth->check();

            $isAdmin = $this->db->isUserGroupAdmin($groupId, $this->auth->getCurrentUserId());
            if (!$isAdmin) {
                Helpers::errorResponse('Нет прав на редактирование', 403);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                Helpers::errorResponse('Неверный JSON', 400);
                return;
            }

            $action = $data['action'] ?? null;
            $valueJson = $data['value'] ?? null;
            
            $value = json_decode($valueJson, true);
            if (!$value) {
                Helpers::errorResponse('Неверные данные', 400);
            }
            
            switch ($action) {
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
    }
?>
