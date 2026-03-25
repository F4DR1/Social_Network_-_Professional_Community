<?php
    require_once 'core/Helpers.php';

    class PostController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }
        
        /**
         * GET /posts/get/{post_id} - получить пост
         */
        public function getPost($postId) {
            Helpers::validatePostId($postId);

            $post = $this->db->fetchOne("
                    SELECT *
                    FROM posts
                    WHERE id = ?
                ",
                [$postId]
            );
            
            if (!$post) {
                Helpers::errorResponse('Пост не найден', 404);
            }
            
            Helpers::jsonResponse(['success' => true, 'post' => $post]);
        }
        
        /**
         * GET /posts/feed - получить все посты в ленте
         */
        public function getAllPostsFeed() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $posts = [];
            // $posts = $this->db->fetchAll("
            //         SELECT
            //             p.*,
                        
            //             g.id as group_id,
            //             g.linkname as group_linkname,
            //             g.name as group_name,
            //             g.photo as group_photo,
                    
            //             u.linkname as author_linkname,
            //             CONCAT(u.firstname, ' ', u.lastname) as author_name
            //         FROM
            //             posts p
            //         INNER JOIN
            //             group_posts gp ON p.id = gp.post_id
            //         INNER JOIN
            //             groups g ON gp.group_id = g.id
            //         INNER JOIN
            //             users u ON p.author_id = u.id
            //         INNER JOIN
            //             group_members gm ON gm.user_id = u.id AND gm.group_id = g.id
            //         WHERE
            //             u.id = ?
            //         ORDER BY
            //             p.created_at DESC
            //     ",
            //     [$currentUserId]
            // );
            
            Helpers::jsonResponse(['success' => true, 'posts' => $posts ?: null]);
        }
        
        /**
         * GET /posts/user/{user_id} - получить все посты пользователя
         */
        public function getAllPostsByUser($userId) {
            Helpers::validateUserId($userId);

            $posts = $this->db->fetchAll("
                    SELECT
                        p.*,
                    
                        u.linkname as author_linkname,
                        CONCAT(u.firstname, ' ', u.lastname) as author_name,
                        u.photo as author_photo
                    FROM
                        posts p
                    INNER JOIN
                        users u ON p.author_id = u.id
                    LEFT JOIN
                        group_posts gp ON gp.post_id = p.id
                    WHERE
                        p.author_id = ?
                        AND
                        gp.post_id IS NULL
                    ORDER BY
                        p.created_at DESC
                ",
                [$userId]
            );
            
            Helpers::jsonResponse(['success' => true, 'posts' => $posts ?: null]);
        }
        
        /**
         * GET /posts/group/{group_id} - получить все посты группы
         */
        public function getAllPostsByGroup($groupId) {
            Helpers::validateGroupId($groupId);

            $posts = $this->db->fetchAll("
                    SELECT
                        p.*,
                        
                        g.id as group_id,
                        g.linkname as group_linkname,
                        g.name as group_name,
                        g.photo as group_photo,
                    
                        u.linkname as author_linkname,
                        CONCAT(u.firstname, ' ', u.lastname) as author_name
                    FROM
                        posts p
                    INNER JOIN
                        group_posts gp ON p.id = gp.post_id
                    INNER JOIN
                        groups g ON gp.group_id = g.id
                    INNER JOIN
                        users u ON p.author_id = u.id
                    WHERE
                        gp.group_id = ?
                    ORDER BY
                        p.created_at DESC
                ",
                [$groupId]
            );
            
            Helpers::jsonResponse(['success' => true, 'posts' => $posts ?: null]);
        }
        
        /**
         * POST /posts/create - создать пост
         */
        public function create() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);
            $groupId = $data['groupId'] ?? null;
            $contentJson = $data['content'] ?? null;
            
            
            $content = json_decode($contentJson, true);
            if (!$content) {
                Helpers::errorResponse('Неверные данные', 400);
            }
            if (!empty($groupId))
                Helpers::validateGroupId($groupId);


            if (empty(trim($content['text']))) {
                Helpers::errorResponse('Для публикации поста нужно указать контент', 400);
                return;
            }


            try {
                $this->db->beginTransaction();

                $this->db->query("
                        INSERT INTO posts (author_id, content, created_at) 
                        VALUES (?, ?, NOW())
                    ",
                    [$currentUserId, $contentJson]
                );
                
                $postId = $this->db->lastInsertId();

                if (!empty($groupId))
                    $this->db->query("
                            INSERT INTO group_posts (group_id, post_id) 
                            VALUES (?, ?)
                        ",
                        [$groupId, $postId]
                    );

                $this->db->commit();
                Helpers::jsonResponse(['success' => true]);

            } catch (Exception $e) {
                $this->db->rollBack();
                Helpers::errorResponse('Ошибка создания поста: ' . $e->getMessage(), 500);
            }
        }
        
        /**
         * POST /posts/delete - удалить пост
         */
        public function delete() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);
            $postId = $data['postId'] ?? null;
            $groupId = $data['groupId'] ?? null;
            
            
            Helpers::validatePostId($postId);
            if (!empty($groupId))
                Helpers::validateGroupId($groupId);


            try {
                $this->db->beginTransaction();

                if (!empty($groupId))
                    $this->db->query("
                            DELETE FROM group_posts
                            WHERE group_id = ? AND post_id = ?
                        ",
                        [$groupId, $postId]
                    );

                $this->db->query("
                        DELETE FROM posts
                        WHERE id = ? AND author_id = ?
                    ",
                    [$postId, $currentUserId]
                );

                $this->db->commit();
                Helpers::jsonResponse(['success' => true]);

            } catch (Exception $e) {
                $this->db->rollBack();
                Helpers::errorResponse('Ошибка удаления поста: ' . $e->getMessage(), 500);
            }
        }
    }
?>
