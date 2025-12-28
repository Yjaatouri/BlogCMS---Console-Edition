<?php

class Storage {
    public static array $users = [];
    public static array $articles = [];
    public static array $categories = [];
    public static int $nextUserId = 1;
    public static int $nextArticleId = 1;
    public static int $nextCategoryId = 1;
    public static int $nextCommentId = 1;
}

abstract class User {
    public function __construct(
        protected int $id,
        protected string $username,
        protected string $password,
        protected ?string $email = null,       
        protected string $role,
        protected DateTime $createdAt,
        protected ?DateTime $lastLogin = null
    ) {}

    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): ?string { return $this->email; }
    public function getRole(): string { return $this->role; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getLastLogin(): ?DateTime { return $this->lastLogin; }
    public function setLastLogin(DateTime $date): void { $this->lastLogin = $date; }

    public function verifyPassword(string $password): bool {
        return $this->password === $password;
    }

    public function canCreateArticle(): bool { return false; }
    public function canEditArticle(Article $a): bool { return false; }
    public function canDeleteArticle(Article $a): bool { return false; }
    public function canManageUsers(): bool { return false; }
    public function canManageCategories(): bool { return false; }
    public function canApproveComments(): bool { return false; }
    public function canAddComment(): bool { return false; }
}

abstract class Moderator extends User {
    public function __construct(
        int $id,
        string $username,
        string $password,
        ?string $email,
        string $role,
        DateTime $createdAt,
        ?DateTime $lastLogin = null
    ) {
        parent::__construct($id, $username, $password, $email, $role, $createdAt, $lastLogin);
    }

    public function canCreateArticle(): bool { return true; }
    public function canEditArticle(Article $a): bool { return true; }
    public function canDeleteArticle(Article $a): bool { return true; }
    public function canManageCategories(): bool { return true; }
    public function canApproveComments(): bool { return true; }
    public function canAddComment(): bool { return true; }
}

class Author extends User {
    public function __construct(
        int $id,
        string $username,
        string $password,
        ?string $email,
        protected string $bio,                    
        DateTime $createdAt,
        ?DateTime $lastLogin = null
    ) {
        parent::__construct($id, $username, $password, $email, 'author', $createdAt, $lastLogin);
    }

    public function getBio(): string { return $this->bio; }

    public function canCreateArticle(): bool { return true; }
    public function canEditArticle(Article $a): bool { return $a->isOwnedBy($this); }
    public function canDeleteArticle(Article $a): bool { return $a->isOwnedBy($this); }
    public function canAddComment(): bool { return true; }
}

class Editor extends Moderator {
    public function __construct(
        int $id,
        string $username,
        string $password,
        ?string $email,
        protected string $moderatorLevel,
        DateTime $createdAt,
        ?DateTime $lastLogin = null
    ) {
        parent::__construct($id, $username, $password, $email, 'editor', $createdAt, $lastLogin);
    }

    public function getModeratorLevel(): string { return $this->moderatorLevel; }
}

class Admin extends Moderator {
    public function __construct(
        int $id,
        string $username,
        string $password,
        ?string $email,
        DateTime $createdAt,
        ?DateTime $lastLogin = null
    ) {
        parent::__construct($id, $username, $password, $email, 'admin', $createdAt, $lastLogin);
    }

    public function canManageUsers(): bool { return true; }
}

class Category {
    public function __construct(
        private int $id,
        private string $name,
        private string $description,
        private ?int $parentId = null,
        private DateTime $createdAt = new DateTime()
    ) {}

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getParentId(): ?int { return $this->parentId; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
}

class Comment {
    private bool $approved = false;

    public function __construct(
        private int $id,
        private string $content,
        private User $author,
        private DateTime $createdAt
    ) {}

    public function getId(): int { return $this->id; }
    public function getContent(): string { return $this->content; }
    public function getAuthor(): User { return $this->author; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function isApproved(): bool { return $this->approved; }
    public function approve(): void { $this->approved = true; }

    public function display(): void {
        $status = $this->approved ? '[Approved]' : '[Pending]';
        echo " - {$status} {$this->author->getUsername()}: {$this->content}\n";
    }
}

class Article {
    private array $comments = [];
    private array $categories = [];
    private string $status = 'draft';

    public function __construct(
        private int $id,
        private string $title,
        private string $content,
        private User $author,
        private DateTime $createdAt,
        private ?DateTime $updatedAt = null,
        private ?DateTime $publishedAt = null
    ) {
        $this->updatedAt = $updatedAt ?? $createdAt;
    }

    public function getId(): int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void {
        $this->title = $title;
        $this->touchUpdatedAt();
    }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): void {
        $this->content = $content;
        $this->touchUpdatedAt();
    }
    public function getAuthor(): User { return $this->author; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): DateTime { return $this->updatedAt ?? $this->createdAt; }
    public function getPublishedAt(): ?DateTime { return $this->publishedAt; }

    private function touchUpdatedAt(): void {
        $this->updatedAt = new DateTime();
    }

    public function isOwnedBy(User $u): bool {
        return $this->author->getId() === $u->getId();
    }

    public function addCategory(Category $c): void {
        $this->categories[$c->getId()] = $c;
    }

    public function removeCategory(int $categoryId): void {
        unset($this->categories[$categoryId]);
    }

    public function getCategories(): array { return array_values($this->categories); }

    public function publish(): bool {
        if (empty($this->categories)) {
            return false;
        }
        $this->status = 'published';
        $this->publishedAt = new DateTime();
        $this->touchUpdatedAt();
        return true;
    }

    public function isPublished(): bool {
        return $this->status === 'published';
    }

    public function addComment(Comment $c): void {
        $this->comments[] = $c;
    }
    public function getComments(): array { return $this->comments; }
    public function getApprovedComments(): array {
        return array_filter($this->comments, fn($c) => $c->isApproved());
    }

    public function display(bool $full = false): void {
        $catInfos = array_map(fn($c) => "{$c->getName()} ({$c->getDescription()})", $this->getCategories());
        $cats = $catInfos ? implode(', ', $catInfos) : 'Aucune';
        $status = $this->isPublished() ? 'published' : 'draft';
        $created = $this->createdAt->format('Y-m-d H:i');
        $updated = $this->getUpdatedAt()->format('Y-m-d H:i');
        $published = $this->publishedAt ? $this->publishedAt->format('Y-m-d H:i') : '—';

        $author = $this->author;
        $bio = ($author instanceof Author) ? " | Bio: " . $author->getBio() : '';

        echo "[{$this->id}] {$this->title} ($status) by {$this->author->getUsername()}{$bio} | Cats: {$cats}\n";
        echo " Created: $created | Updated: $updated | Published: $published\n";

        if ($full && $this->isPublished()) {
            echo str_repeat("-", 70) . "\n";
            echo $this->content . "\n";
            echo str_repeat("-", 70) . "\n";
            echo "Commentaires:\n";
            if (empty($this->getApprovedComments())) {
                echo " Aucun commentaire approuvé.\n";
            } else {
                foreach ($this->getApprovedComments() as $comment) {
                    $comment->display();
                }
            }
        } elseif ($full) {
            echo " (Article non publié - contenu visible uniquement pour les membres)\n";
        }
    }
}

class BlogCMS {
    private ?User $currentUser = null;

    public function __construct() {
        $this->seed();
    }

    private function seed(): void {
        $now = new DateTime();
        Storage::$users[] = new Admin(Storage::$nextUserId++, 'admin', 'admin123', 'admin@example.com', $now);
        Storage::$users[] = new Author(Storage::$nextUserId++, 'yahya', '1234', 'yahya@example.com', 'Passionate tech writer and developer.', $now);
        Storage::$users[] = new Editor(Storage::$nextUserId++, 'editor1', 'edit123', 'editor1@example.com', 'senior', $now);

        Storage::$categories[] = new Category(Storage::$nextCategoryId++, 'Tech', 'Everything related to technology and gadgets');
        Storage::$categories[] = new Category(Storage::$nextCategoryId++, 'Programming', 'Coding, languages, frameworks, and development tips', Storage::$categories[0]->getId());
        Storage::$categories[] = new Category(Storage::$nextCategoryId++, 'Life', 'Personal experiences, lifestyle, and reflections');

        $article = new Article(
            Storage::$nextArticleId++,
            'Welcome to Our Blog!',
            "This is the first published article. Feel free to read and comment!",
            Storage::$users[0],
            new DateTime()
        );
        $article->addCategory(Storage::$categories[0]);
        $article->publish();
        Storage::$articles[] = $article;
    }

    public function run(): void {
        while (true) {
            if (!$this->currentUser) {
                $this->showVisitorMenu();
            } else {
                $this->showAuthenticatedMenu();
            }

            $choice = trim(fgets(STDIN));

            if (!$this->currentUser) {
                $this->handleVisitorChoice($choice);
            } else {
                $this->handleAuthenticatedChoice($choice);
            }
        }
    }

    private function showVisitorMenu(): void {
        echo "\n=== WELCOME TO BLOGCMS (Visitor) ===\n";
        echo "1. View published articles\n";
        echo "2. View full article\n";
        echo "3. Sign up\n";
        echo "4. Log in\n";
        echo "0. Exit\n> ";
    }

    private function showAuthenticatedMenu(): void {
        $role = $this->currentUser->getRole();
        $username = $this->currentUser->getUsername();
        echo "\n=== BLOGCMS MENU ($role: $username) ===\n";

        echo "1. View all articles (incl. drafts)\n";

        if ($this->currentUser->canCreateArticle()) {
            echo "2. My articles\n";
        }

        $next = $this->currentUser->canCreateArticle() ? 3 : 2;

        echo $next++ . ". View full article\n";

        if ($this->currentUser->canCreateArticle()) {
            echo $next++ . ". Create article\n";
            echo $next++ . ". Edit article\n";
            echo $next++ . ". Publish article\n";
        }

        if ($this->currentUser->canAddComment()) {
            echo $next++ . ". Add comment (on published articles)\n";
        }

        if ($this->currentUser->canApproveComments()) {
            echo $next++ . ". Approve comments\n";
        }

        if ($this->currentUser->canManageCategories()) {
            echo $next++ . ". Manage categories\n";
        }

        echo $next++ . ". Delete article\n";

        if ($this->currentUser->canManageUsers()) {
            echo $next++ . ". Manage users\n";
            echo $next++ . ". Change user role\n";
        }

        echo "0. Log out\n> ";
    }

    private function handleVisitorChoice(string $choice): void {
        match ($choice) {
            '1' => $this->listPublishedArticles(),
            '2' => $this->viewArticleAsVisitor(),
            '3' => $this->signup(),
            '4' => $this->login(),
            '0' => exit("Goodbye!\n"),
            default => $this->invalid(),
        };
    }

    private function handleAuthenticatedChoice(string $choice): void {
        if ($choice === '2' && $this->currentUser->canCreateArticle()) {
            $this->listMyArticles();
            return;
        }

        if ($this->currentUser->canCreateArticle() && (int)$choice >= 3) {
            $choice = (string)((int)$choice - 1);
        }

        match ($choice) {
            '1' => $this->listArticles(),
            '2' => $this->viewArticle(),
            '3' => $this->currentUser->canCreateArticle() ? $this->createArticle() : $this->invalid(),
            '4' => $this->currentUser->canCreateArticle() ? $this->editArticle() : $this->invalid(),
            '5' => $this->currentUser->canCreateArticle() ? $this->publishArticle() : $this->invalid(),
            '6' => $this->currentUser->canAddComment() ? $this->addComment() : $this->invalid(),
            '7' => $this->currentUser->canApproveComments() ? $this->approveComments() : $this->invalid(),
            '8' => $this->currentUser->canManageCategories() ? $this->manageCategories() : $this->invalid(),
            '9' => $this->deleteArticle(),
            '10' => $this->currentUser->canManageUsers() ? $this->manageUsers() : $this->invalid(),
            '11' => $this->currentUser->canManageUsers() ? $this->changeUserRole() : $this->invalid(),
            '0' => $this->logout(),
            default => $this->invalid(),
        };
    }

    private function invalid(): void {
        echo "Invalid option or insufficient permissions.\n";
    }

    private function listMyArticles(): void {
        $myArticles = array_filter(Storage::$articles, fn($a) => $a->isOwnedBy($this->currentUser));

        if (empty($myArticles)) {
            echo "\nYou haven't written any articles yet.\n";
            return;
        }

        echo "\n=== MY ARTICLES ===\n";
        foreach ($myArticles as $article) {
            $article->display();
        }
    }

    private function approveComments(): void {
        echo "\n--- APPROVE COMMENTS ---\n";
        $pending = [];
        foreach (Storage::$articles as $article) {
            if (!$article->isPublished()) continue;
            foreach ($article->getComments() as $comment) {
                if (!$comment->isApproved()) {
                    $pending[] = ['article' => $article, 'comment' => $comment];
                }
            }
        }

        if (empty($pending)) {
            echo "No pending comments.\n";
            return;
        }

        foreach ($pending as $i => $item) {
            echo "[{$i}] Article [{$item['article']->getId()}] {$item['article']->getTitle()}\n";
            $item['comment']->display();
        }

        echo "\nComment index to approve (or Enter to cancel): ";
        $idx = trim(fgets(STDIN));
        if ($idx === '' || !isset($pending[$idx])) {
            echo "Cancelled.\n";
            return;
        }

        $pending[$idx]['comment']->approve();
        echo "Comment approved!\n";
    }

    private function deleteArticle(): void {
        echo "Article ID to delete: ";
        $id = (int)trim(fgets(STDIN));
        $article = $this->findArticle($id);

        if (!$article) {
            echo "Article not found.\n";
            return;
        }

        if (!$this->currentUser->canDeleteArticle($article)) {
            echo "Permission denied.\n";
            return;
        }

        foreach (Storage::$articles as $k => $a) {
            if ($a->getId() === $id) {
                unset(Storage::$articles[$k]);
                Storage::$articles = array_values(Storage::$articles);
                echo "Article deleted.\n";
                return;
            }
        }
    }

    private function manageUsers(): void {
        while (true) {
            echo "\n--- MANAGE USERS ---\n";
            foreach (Storage::$users as $u) {
                $created = $u->getCreatedAt()->format('Y-m-d H:i:s');
                $last = $u->getLastLogin() ? $u->getLastLogin()->format('Y-m-d H:i:s') : 'Never';
                $email = $u->getEmail() ? " | Email: {$u->getEmail()}" : '';
                $bio = ($u instanceof Author) ? " | Bio: {$u->getBio()}" : '';
                $extra = ($u instanceof Editor) ? " | Level: {$u->getModeratorLevel()}" : '';
                echo "[{$u->getId()}] {$u->getUsername()} ({$u->getRole()})$email$bio$extra | Created: $created | Last login: $last\n";
            }
            echo "1. Create user\n";
            echo "2. Delete user\n";
            echo "0. Back\n> ";
            $ch = trim(fgets(STDIN));
            if ($ch === '0') break;
            if ($ch === '1') $this->createUserViaAdmin();
            elseif ($ch === '2') $this->deleteUser();
        }
    }

    private function createUserViaAdmin(): void {
        echo "Username: "; $un = trim(fgets(STDIN));
        echo "Password: "; $pw = trim(fgets(STDIN));
        echo "Email (optional): "; $email = trim(fgets(STDIN));
        $email = $email === '' ? null : $email;
        echo "Role (author/editor/admin): "; $role = strtolower(trim(fgets(STDIN)));
        $now = new DateTime();

        if ($role === 'author') {
            echo "Bio: "; $bio = trim(fgets(STDIN));
            if ($bio === '') $bio = 'No bio provided.';
            Storage::$users[] = new Author(Storage::$nextUserId++, $un, $pw, $email, $bio, $now);
        } elseif ($role === 'editor') {
            echo "Moderator level (e.g., junior/senior): ";
            $level = trim(fgets(STDIN));
            Storage::$users[] = new Editor(Storage::$nextUserId++, $un, $pw, $email, $level, $now);
        } elseif ($role === 'admin') {
            Storage::$users[] = new Admin(Storage::$nextUserId++, $un, $pw, $email, $now);
        } else {
            echo "Invalid role.\n";
            return;
        }
        echo "User created successfully.\n";
    }

    private function deleteUser(): void {
        echo "User ID to delete: ";
        $userId = (int)trim(fgets(STDIN));
        $targetIndex = null;
        foreach (Storage::$users as $i => $u) {
            if ($u->getId() === $userId) {
                if ($u->getId() === $this->currentUser->getId()) {
                    echo "You cannot delete yourself.\n";
                    return;
                }
                $targetIndex = $i;
                break;
            }
        }

        if ($targetIndex === null) {
            echo "User not found.\n";
            return;
        }

        $targetUser = Storage::$users[$targetIndex];
        if ($targetUser instanceof Author) {
            Storage::$articles = array_filter(Storage::$articles, fn($a) => !$a->isOwnedBy($targetUser));
            Storage::$articles = array_values(Storage::$articles);
            echo "All articles by this author have been deleted.\n";
        }

        unset(Storage::$users[$targetIndex]);
        Storage::$users = array_values(Storage::$users);
        echo "User deleted successfully.\n";
    }

    private function changeUserRole(): void {
        
        echo "\n--- CHANGE USER ROLE ---\n";
        foreach (Storage::$users as $u) {
            $created = $u->getCreatedAt()->format('Y-m-d H:i:s');
            $last = $u->getLastLogin() ? $u->getLastLogin()->format('Y-m-d H:i:s') : 'Never';
            $email = $u->getEmail() ? " | Email: {$u->getEmail()}" : '';
            $bio = ($u instanceof Author) ? " | Bio: {$u->getBio()}" : '';
            $extra = ($u instanceof Editor) ? " | Level: {$u->getModeratorLevel()}" : '';
            echo "[{$u->getId()}] {$u->getUsername()} (current: {$u->getRole()})$email$bio$extra | Created: $created | Last login: $last\n";
        }
    }

    private function listPublishedArticles(): void {
        $published = array_filter(Storage::$articles, fn($a) => $a->isPublished());
        if (empty($published)) {
            echo "No published articles yet.\n";
            return;
        }
        echo "\n=== PUBLISHED ARTICLES ===\n";
        foreach ($published as $article) {
            $article->display();
        }
    }

    private function listArticles(): void {
        if (empty(Storage::$articles)) {
            echo "No articles.\n";
            return;
        }
        echo "\n=== ALL ARTICLES (incl. drafts) ===\n";
        foreach (Storage::$articles as $article) {
            $article->display();
        }
    }

    private function viewArticleAsVisitor(): void {
        echo "Article ID: ";
        $id = (int)trim(fgets(STDIN));
        $article = $this->findArticle($id);
        if (!$article || !$article->isPublished()) {
            echo "Article not found or not published.\n";
            return;
        }
        $article->display(full: true);
    }

    private function viewArticle(): void {
        echo "Article ID: ";
        $id = (int)trim(fgets(STDIN));
        $article = $this->findArticle($id);
        if (!$article) {
            echo "Article not found\n";
            return;
        }
        $article->display(full: true);
    }

    private function signup(): void {
        echo "\n=== SIGN UP ===\n";
        echo "Username: ";
        $username = trim(fgets(STDIN));
        foreach (Storage::$users as $user) {
            if ($user->getUsername() === $username) {
                echo "Username already taken.\n";
                return;
            }
        }
        echo "Password: ";
        $password = trim(fgets(STDIN));
        echo "Email: ";
        $emailInput = trim(fgets(STDIN));
        $email = $emailInput === '' ? null : $emailInput;
        echo "Bio: ";
        $bio = trim(fgets(STDIN));
        if ($bio === '') $bio = 'No bio provided.';

        $now = new DateTime();
        Storage::$users[] = new Author(
            Storage::$nextUserId++,
            $username,
            $password,
            $email,
            $bio,
            $now
        );
        echo "Account created on {$now->format('Y-m-d H:i:s')}! You are now an author. Log in to start writing.\n";
    }

    private function login(): void {
        echo "\nUsername or Email: ";
        $input = trim(fgets(STDIN));
        echo "Password: ";
        $p = trim(fgets(STDIN));
        $now = new DateTime();

        foreach (Storage::$users as $user) {
            $matches = ($user->getUsername() === $input) || ($user->getEmail() && $user->getEmail() === $input);
            if ($matches && $user->verifyPassword($p)) {
                $user->setLastLogin($now);
                $this->currentUser = $user;
                $last = $user->getLastLogin()->format('Y-m-d H:i:s');
                echo "Logged in as {$user->getRole()} ({$user->getUsername()}) | Last login: $last\n";
                return;
            }
        }
        echo "Invalid credentials\n";
    }

    private function logout(): void {
        $this->currentUser = null;
        echo "Logged out successfully.\n";
    }

    private function createArticle(): void {
        echo "\n=== CREATE ARTICLE ===\n";
        echo "Title: ";
        $title = trim(fgets(STDIN));
        echo "Content (type END on a new line to finish):\n";
        $content = "";
        while (true) {
            $line = fgets(STDIN);
            if (trim($line) === "END") break;
            $content .= $line;
        }
        $now = new DateTime();
        $article = new Article(
            Storage::$nextArticleId++,
            $title,
            trim($content),
            $this->currentUser,
            $now,
            $now
        );
        $this->chooseCategories($article);
        Storage::$articles[] = $article;
        echo "Article created as draft on {$now->format('Y-m-d H:i:s')}.\n";
    }

    private function editArticle(): void {
        echo "Article ID to edit: ";
        $id = (int)trim(fgets(STDIN));
        $article = $this->findArticle($id);
        if (!$article || !$this->currentUser->canEditArticle($article)) {
            echo "Not found or permission denied\n";
            return;
        }
        echo "New title (Enter to keep): ";
        $title = trim(fgets(STDIN));
        if ($title !== '') $article->setTitle($title);

        echo "Update content? (y/n): ";
        if (strtolower(trim(fgets(STDIN))) === 'y') {
            echo "New content (END to finish):\n";
            $content = "";
            while (true) {
                $line = fgets(STDIN);
                if (trim($line) === "END") break;
                $content .= $line;
            }
            $article->setContent(trim($content));
        }
        echo "Article updated on " . $article->getUpdatedAt()->format('Y-m-d H:i:s') . ".\n";
    }

    private function publishArticle(): void {
        echo "Article ID to publish: ";
        $id = (int)trim(fgets(STDIN));
        $article = $this->findArticle($id);
        if (!$article || !$this->currentUser->canEditArticle($article)) {
            echo "Not found or permission denied\n";
            return;
        }
        if ($article->publish()) {
            echo "Article published on " . $article->getPublishedAt()->format('Y-m-d H:i:s') . "!\n";
        } else {
            echo "Cannot publish: article must have at least one category.\n";
        }
    }

    private function addComment(): void {
        echo "Article ID: ";
        $id = (int)trim(fgets(STDIN));
        $article = $this->findArticle($id);
        if (!$article || !$article->isPublished()) {
            echo "Article not found or not published.\n";
            return;
        }
        echo "Your comment: ";
        $content = trim(fgets(STDIN));
        $article->addComment(new Comment(
            Storage::$nextCommentId++,
            $content,
            $this->currentUser,
            new DateTime()
        ));
        echo "Comment added (pending approval).\n";
    }

    private function manageCategories(): void {
        while (true) {
            echo "\n--- MANAGE CATEGORIES ---\n";
            $this->listCategoriesTree();
            echo "1. Create category\n";
            echo "2. Delete category\n";
            echo "0. Back\n> ";
            $ch = trim(fgets(STDIN));

            if ($ch === '0') break;

            if ($ch === '1') {
                echo "Name: ";
                $name = trim(fgets(STDIN));
                echo "Description: ";
                $description = trim(fgets(STDIN));
                if ($description === '') $description = 'No description provided.';
                echo "Parent ID (Enter for none): ";
                $parent = trim(fgets(STDIN));
                $parentId = $parent === '' ? null : (int)$parent;

                if ($parentId !== null && !$this->findCategory($parentId)) {
                    echo "Parent category not found.\n";
                    continue;
                }

                Storage::$categories[] = new Category(
                    Storage::$nextCategoryId++,
                    $name,
                    $description,
                    $parentId
                );
                echo "Category created.\n";

            } elseif ($ch === '2') {
                $this->deleteCategory();

            } else {
                echo "Invalid option.\n";
            }
        }
    }

    private function deleteCategory(): void {
        echo "Category ID to delete: ";
        $input = trim(fgets(STDIN));
        if ($input === '') {
            echo "Cancelled.\n";
            return;
        }

        $id = (int)$input;
        $category = $this->findCategory($id);

        if (!$category) {
            echo "Category not found.\n";
            return;
        }

        echo "Are you sure you want to delete category [{$id}] '{$category->getName()}'? (y/N): ";
        $confirm = strtolower(trim(fgets(STDIN)));
        if ($confirm !== 'y' && $confirm !== 'yes') {
            echo "Cancelled.\n";
            return;
        }

        foreach (Storage::$articles as $article) {
            $article->removeCategory($id);
        }

        foreach (Storage::$categories as $k => $cat) {
            if ($cat->getId() === $id) {
                unset(Storage::$categories[$k]);
                break;
            }
        }
        Storage::$categories = array_values(Storage::$categories);

        echo "Category '{$category->getName()}' (ID: $id) deleted successfully.\n";
        echo "It has been removed from all articles.\n";
    }

    private function findCategory(int $id): ?Category {
        foreach (Storage::$categories as $cat) {
            if ($cat->getId() === $id) {
                return $cat;
            }
        }
        return null;
    }

    private function chooseCategories(Article $article): void {
        if (empty(Storage::$categories)) {
            echo "No categories available yet.\n";
            return;
        }
        echo "\nAvailable categories:\n";
        $this->listCategoriesTree();
        echo "Category IDs (comma-separated, or Enter for none): ";
        $input = trim(fgets(STDIN));
        if ($input === '') return;
        $ids = array_map('intval', explode(',', $input));
        foreach (Storage::$categories as $cat) {
            if (in_array($cat->getId(), $ids)) {
                $article->addCategory($cat);
            }
        }
    }

    private function listCategoriesTree(?int $parentId = null, int $level = 0): void {
        foreach (Storage::$categories as $cat) {
            if ($cat->getParentId() === $parentId) {
                $created = $cat->getCreatedAt()->format('Y-m-d');
                $descPreview = strlen($cat->getDescription()) > 50 
                    ? substr($cat->getDescription(), 0, 47) . '...' 
                    : $cat->getDescription();
                echo str_repeat("  ", $level) . "└─ [{$cat->getId()}] {$cat->getName()} — {$descPreview} (created: $created)\n";
                $this->listCategoriesTree($cat->getId(), $level + 1);
            }
        }
    }

    private function findArticle(int $id): ?Article {
        foreach (Storage::$articles as $a) {
            if ($a->getId() === $id) return $a;
        }
        return null;
    }
}


$app = new BlogCMS();
$app->run();