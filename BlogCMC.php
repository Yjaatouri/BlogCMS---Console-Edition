<?php 
    // Static storage arrays to simulate database
    class Storage {
        public static array $articles = [];
        public static array $categories = [];
        public static array $comments = [];
        public static array $users = [];
        public static array $articleCategories = []; // article_id => [category_ids]
        public static array $articleComments = []; // article_id => [comment_ids]
        public static array $categoryParents = []; // category_id => parent_id
        public static ?user $loggedInUser = null;
    }

    class article {
        private int $id_article;
        private string $titre;
        private string $content;
        private string $excerpt;
        private string $status;
        private string $author;
        private string $createdAtA;
        private DateTime $publishedAtA;
        private DateTime $updatedAtA;
        
        function __construct($id_article,$titre,$content,$excerpt, $status,$author,$createdAtA,$publishedAtA,$updatedAtA)
        {
            $this->id_article = $id_article;
            $this->titre = $titre;
            $this->content = $content;
            $this->excerpt = $excerpt;
            $this->status = $status;
            $this->author = $author;
            $this->createdAtA = $createdAtA;
            $this->publishedAtA = $publishedAtA;
            $this->updatedAtA = $updatedAtA;
        }
        
        public function getId() {
            return $this->id_article;
        }
        
        public function addCategory ($id_categorie){
            if (!isset(Storage::$articleCategories[$this->id_article])) {
                Storage::$articleCategories[$this->id_article] = [];
            }
            if (!in_array($id_categorie, Storage::$articleCategories[$this->id_article])) {
                Storage::$articleCategories[$this->id_article][] = $id_categorie;
                return true;
            }
            return false;
        }
        
        public function removeCategorie($id_categorie){
            if (isset(Storage::$articleCategories[$this->id_article])) {
                $key = array_search($id_categorie, Storage::$articleCategories[$this->id_article]);
                if ($key !== false) {
                    unset(Storage::$articleCategories[$this->id_article][$key]);
                    Storage::$articleCategories[$this->id_article] = array_values(Storage::$articleCategories[$this->id_article]);
                    return true;
                }
            }
            return false;
        }
        
        public function addComment($comment){
            if (!isset(Storage::$articleComments[$this->id_article])) {
                Storage::$articleComments[$this->id_article] = [];
            }
            $commentId = $comment->getId();
            if (!in_array($commentId, Storage::$articleComments[$this->id_article])) {
                Storage::$articleComments[$this->id_article][] = $commentId;
                return true;
            }
            return false;
        }
        
        public function getComments(){
            $comments = [];
            if (isset(Storage::$articleComments[$this->id_article])) {
                foreach (Storage::$articleComments[$this->id_article] as $commentId) {
                    foreach (Storage::$comments as $comment) {
                        if ($comment->getId() === $commentId) {
                            $comments[] = $comment;
                        }
                    }
                }
            }
            return $comments;
        }
    }
    class comment {
        private int $id_comment ;
        private string $content;
        private DateTime $dateC;
        private bool $approved = false;
        
        function __construct($id_comment,$content,$dateC)
        {
            $this->id_comment = $id_comment;
            $this->content = $content;
            $this->dateC = $dateC;
        }
        
        public function getId() {
            return $this->id_comment;
        }
        
        public function getContent() {
            return $this->content;
        }
        
        public function approve() {
            $this->approved = true;
        }
        
        public function isApproved() {
            return $this->approved;
        }
    }
    class categorie {
        private int $id_categorie;
        private string $nameCa;
        private string $description;
        private DateTime $dateCA;
        
        function __construct($id_categorie, $nameCa ,$description ,$dateCA)
        {
            $this->id_categorie = $id_categorie;
            $this->nameCa = $nameCa;
            $this->description = $description;
            $this->dateCA = $dateCA;
        }
        
        public function getId() {
            return $this->id_categorie;
        }
        
        public function getName() {
            return $this->nameCa;
        }
        
        public function setParent($parentId) {
            Storage::$categoryParents[$this->id_categorie] = $parentId;
        }
        
        public function getParent(){
            if (isset(Storage::$categoryParents[$this->id_categorie])) {
                $parentId = Storage::$categoryParents[$this->id_categorie];
                foreach (Storage::$categories as $category) {
                    if ($category->getId() === $parentId) {
                        return $category;
                    }
                }
            }
            return null;
        }
        
        public function getTree(){
            $tree = [$this];
            $children = [];
            foreach (Storage::$categoryParents as $childId => $parentId) {
                if ($parentId === $this->id_categorie) {
                    foreach (Storage::$categories as $category) {
                        if ($category->getId() === $childId) {
                            $children[] = $category;
                            $tree = array_merge($tree, $category->getTree());
                        }
                    }
                }
            }
            return $tree;
        }
    }
class user {
    protected int $id_user;
    protected string $username;
    protected string $email;
    protected string $password;
    protected DateTime $createdAT; 
    protected DateTime $lastLogin;
    
    function __construct($id_user , $username , $email , $password , $createdAT , $lastLogin)
    {
        $this->id_user = $id_user;
        $this->username = $username;
        $this->email = $email;
        $this->password  = $password;
        $this->createdAT = $createdAT;
        $this->lastLogin = $lastLogin;
    }
    
    public function getId() {
        return $this->id_user;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function login($email , $password){
        if ($this->email === $email && $this->password === $password) {
            Storage::$loggedInUser = $this;
            $this->lastLogin = new DateTime();
            return true;
        }
        return false;
    }
    
    public function logout(){
        Storage::$loggedInUser = null;
        return true;
    }
}
class moderateur extends user {
    public function approvComment($id_comment){
        foreach (Storage::$comments as $comment) {
            if ($comment->getId() === $id_comment) {
                $comment->approve();
                return true;
            }
        }
        return false;
    }
    
    public function deleteCommnet($id_comment){
        foreach (Storage::$comments as $key => $comment) {
            if ($comment->getId() === $id_comment) {
                unset(Storage::$comments[$key]);
                Storage::$comments = array_values(Storage::$comments);
                // Remove from article comments
                foreach (Storage::$articleComments as $articleId => $commentIds) {
                    $index = array_search($id_comment, $commentIds);
                    if ($index !== false) {
                        unset(Storage::$articleComments[$articleId][$index]);
                        Storage::$articleComments[$articleId] = array_values(Storage::$articleComments[$articleId]);
                    }
                }
                return true;
            }
        }
        return false;
    }
    
    public function createCategory($nameCa, $description = ""){
        $newId = count(Storage::$categories) + 1;
        $category = new categorie($newId, $nameCa, $description, new DateTime());
        Storage::$categories[] = $category;
        return $category;
    } 
    
    public function deleteCategorie($id_categorie){
        foreach (Storage::$categories as $key => $category) {
            if ($category->getId() === $id_categorie) {
                unset(Storage::$categories[$key]);
                Storage::$categories = array_values(Storage::$categories);
                // Remove from article categories
                foreach (Storage::$articleCategories as $articleId => $categoryIds) {
                    $index = array_search($id_categorie, $categoryIds);
                    if ($index !== false) {
                        unset(Storage::$articleCategories[$articleId][$index]);
                        Storage::$articleCategories[$articleId] = array_values(Storage::$articleCategories[$articleId]);
                    }
                }
                // Remove parent relationship
                if (isset(Storage::$categoryParents[$id_categorie])) {
                    unset(Storage::$categoryParents[$id_categorie]);
                }
                return true;
            }
        }
        return false;
    }
    
    public function publishArticle($id_article){
        foreach (Storage::$articles as $article) {
            if ($article->getId() === $id_article) {
                // Using reflection to update private status property
                $reflection = new ReflectionClass($article);
                $property = $reflection->getProperty('status');
                $property->setAccessible(true);
                $property->setValue($article, 'published');
                return true;
            }
        }
        return false;
    }
    
    public function deleteAnyArticle($id_article){
        foreach (Storage::$articles as $key => $article) {
            if ($article->getId() === $id_article) {
                unset(Storage::$articles[$key]);
                Storage::$articles = array_values(Storage::$articles);
                // Remove associated categories and comments
                if (isset(Storage::$articleCategories[$id_article])) {
                    unset(Storage::$articleCategories[$id_article]);
                }
                if (isset(Storage::$articleComments[$id_article])) {
                    unset(Storage::$articleComments[$id_article]);
                }
                return true;
            }
        }
        return false;
    }
}
class author extends user {
    private string $bio;
    
    function __construct($id_user, $username, $email, $password, $createdAT, $lastLogin, $bio){
        parent::__construct($id_user, $username, $email, $password, $createdAT, $lastLogin);
        $this->bio = $bio;
    }
    
    public function createArticle($titre, $content, $excerpt = ""){
        $newId = count(Storage::$articles) + 1;
        $now = new DateTime();
        $article = new article(
            $newId,
            $titre,
            $content,
            $excerpt,
            'draft',
            $this->username,
            $now->format('Y-m-d H:i:s'),
            $now,
            $now
        );
        Storage::$articles[] = $article;
        return $article;
    }
    
    public function updateOwnArticle($id_article, $titre = null, $content = null, $excerpt = null){
        foreach (Storage::$articles as $article) {
            if ($article->getId() === $id_article) {
                // Check if article belongs to this author
                $reflection = new ReflectionClass($article);
                $authorProp = $reflection->getProperty('author');
                $authorProp->setAccessible(true);
                if ($authorProp->getValue($article) !== $this->username) {
                    return false; // Not the owner
                }
                
                // Update fields
                if ($titre !== null) {
                    $titreProp = $reflection->getProperty('titre');
                    $titreProp->setAccessible(true);
                    $titreProp->setValue($article, $titre);
                }
                if ($content !== null) {
                    $contentProp = $reflection->getProperty('content');
                    $contentProp->setAccessible(true);
                    $contentProp->setValue($article, $content);
                }
                if ($excerpt !== null) {
                    $excerptProp = $reflection->getProperty('excerpt');
                    $excerptProp->setAccessible(true);
                    $excerptProp->setValue($article, $excerpt);
                }
                
                // Update updatedAt
                $updatedProp = $reflection->getProperty('updatedAtA');
                $updatedProp->setAccessible(true);
                $updatedProp->setValue($article, new DateTime());
                
                return true;
            }
        }
        return false;
    }
    
    public function deleteOwnArticle($id_article){
        foreach (Storage::$articles as $key => $article) {
            if ($article->getId() === $id_article) {
                // Check if article belongs to this author
                $reflection = new ReflectionClass($article);
                $authorProp = $reflection->getProperty('author');
                $authorProp->setAccessible(true);
                if ($authorProp->getValue($article) !== $this->username) {
                    return false; // Not the owner
                }
                
                unset(Storage::$articles[$key]);
                Storage::$articles = array_values(Storage::$articles);
                // Remove associated categories and comments
                if (isset(Storage::$articleCategories[$id_article])) {
                    unset(Storage::$articleCategories[$id_article]);
                }
                if (isset(Storage::$articleComments[$id_article])) {
                    unset(Storage::$articleComments[$id_article]);
                }
                return true;
            }
        }
        return false;
    }
    
    public function getMyArticle(){
        $myArticles = [];
        foreach (Storage::$articles as $article) {
            $reflection = new ReflectionClass($article);
            $authorProp = $reflection->getProperty('author');
            $authorProp->setAccessible(true);
            if ($authorProp->getValue($article) === $this->username) {
                $myArticles[] = $article;
            }
        }
        return $myArticles;
    }
}
    class admin extends moderateur {
        private bool $isSuperAdmin;
        
        function __construct($id_user, $username, $email, $password, $createdAT, $lastLogin, $isSuperAdmin){
            parent::__construct($id_user, $username, $email, $password, $createdAT, $lastLogin);
            $this->isSuperAdmin = $isSuperAdmin;
        }
        
        public function createUser($username, $email, $password, $role = 'user'){
            $newId = count(Storage::$users) + 1;
            $now = new DateTime();
            
            switch($role) {
                case 'author':
                    $user = new author($newId, $username, $email, $password, $now, $now, '');
                    break;
                case 'moderator':
                    $user = new moderateur($newId, $username, $email, $password, $now, $now);
                    break;
                case 'admin':
                    $user = new admin($newId, $username, $email, $password, $now, $now, false);
                    break;
                case 'editor':
                    $user = new editeur($newId, $username, $email, $password, $now, $now, 'standard');
                    break;
                default:
                    $user = new user($newId, $username, $email, $password, $now, $now);
            }
            
            Storage::$users[] = $user;
            return $user;
        }
        
        public function deleteUser($id_user){
            if ($id_user === $this->id_user) {
                return false; // Cannot delete yourself
            }
            
            foreach (Storage::$users as $key => $user) {
                if ($user->getId() === $id_user) {
                    unset(Storage::$users[$key]);
                    Storage::$users = array_values(Storage::$users);
                    return true;
                }
            }
            return false;
        }
        
        public function updateUserRole($id_user, $newRole){
            foreach (Storage::$users as $key => $user) {
                if ($user->getId() === $id_user) {
                    // Get user data
                    $username = $user->getUsername();
                    $email = $user->getEmail();
                    $reflection = new ReflectionClass($user);
                    $passwordProp = $reflection->getProperty('password');
                    $passwordProp->setAccessible(true);
                    $password = $passwordProp->getValue($user);
                    
                    $createdProp = $reflection->getProperty('createdAT');
                    $createdProp->setAccessible(true);
                    $createdAT = $createdProp->getValue($user);
                    
                    $lastLoginProp = $reflection->getProperty('lastLogin');
                    $lastLoginProp->setAccessible(true);
                    $lastLogin = $lastLoginProp->getValue($user);
                    
                    // Create new user with new role
                    unset(Storage::$users[$key]);
                    Storage::$users = array_values(Storage::$users);
                    
                    switch($newRole) {
                        case 'author':
                            $newUser = new author($id_user, $username, $email, $password, $createdAT, $lastLogin, '');
                            break;
                        case 'moderator':
                            $newUser = new moderateur($id_user, $username, $email, $password, $createdAT, $lastLogin);
                            break;
                        case 'admin':
                            $newUser = new admin($id_user, $username, $email, $password, $createdAT, $lastLogin, false);
                            break;
                        case 'editor':
                            $newUser = new editeur($id_user, $username, $email, $password, $createdAT, $lastLogin, 'standard');
                            break;
                        default:
                            $newUser = new user($id_user, $username, $email, $password, $createdAT, $lastLogin);
                    }
                    
                    Storage::$users[] = $newUser;
                    return $newUser;
                }
            }
            return false;
        }
        
        public function listAllUsers(){
            return Storage::$users;
        }
    }
    class editeur extends moderateur {
        private string $moderationLevel;
        
        function __construct($id_user, $username, $email, $password, $createdAT, $lastLogin, $moderationLevel = 'standard'){
            parent::__construct($id_user, $username, $email, $password, $createdAT, $lastLogin);
            $this->moderationLevel = $moderationLevel;
        }
        
        public function getModerationLevel() {
            return $this->moderationLevel;
        }
        
        public function setModerationLevel($level) {
            $this->moderationLevel = $level;
        }
    }
?>