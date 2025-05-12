<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    PasswordResetController,
    UserController,
    SkillController,
    CategoryController,
    UserSkillController,
    UserInterestController,
    getSuggestedUsers,
    PostController,
    SearchController,
    UserExchangesController,
    ReportsController,
    RequestsController,
    MessagesController,
    SessionsController
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth & Password Reset
Route::post('/firebase-login', [AuthController::class, 'login']);
Route::post('/password-reset', [PasswordResetController::class, 'sendResetLink']);

// Users
Route::prefix('users')->group(function () {
    Route::get('/',             [UserController::class, 'getUsers']);
    Route::get('/{id}',         [UserController::class, 'getUser']);
    Route::post('/',            [UserController::class, 'addUser']);
    Route::put('/{id}',         [UserController::class, 'updateUser']);
    Route::delete('/{id}',      [UserController::class, 'deleteUser']);
    Route::get('/suggested/{id}', [getSuggestedUsers::class, 'getSuggestedUsers']);
});

// Skills & Categories
Route::prefix('skills')->group(function () {
    Route::get('/',                       [SkillController::class, 'getSkills']);
    Route::get('/{id}',                   [SkillController::class, 'getSkill']);
    Route::post('/',                      [SkillController::class, 'addSkill']);
    Route::put('/{id}',                   [SkillController::class, 'updateSkill']);
    Route::delete('/{id}',                [SkillController::class, 'deleteSkill']);
    Route::get('/category/{categoryId}',  [SkillController::class, 'getSkillsByCategory']);
    Route::get('/available/{userId}',     [SkillController::class, 'getAvailableSkillsForUser']);
});

Route::prefix('categories')->group(function () {
    Route::get('/',            [CategoryController::class, 'getCategories']);
    Route::get('/{id}',        [CategoryController::class, 'getCategory']);
    Route::post('/',           [CategoryController::class, 'addCategory']);
    Route::put('/{id}',        [CategoryController::class, 'updateCategory']);
    Route::delete('/{id}',     [CategoryController::class, 'deleteCategory']);
});

// User Skills & Interests
Route::prefix('user_skills')->group(function () {
    Route::get('/{userId}',                    [UserSkillController::class, 'getUserSkills']);
    Route::post('/{userId}',                   [UserSkillController::class, 'upsertUserSkills']);
    Route::delete('/{userId}/{skillKey}',      [UserSkillController::class, 'deleteUserSkill']);
    Route::get('/{userId}/names',              [UserSkillController::class, 'getUserSkillsWithNames']);
});

Route::prefix('user_interests')->group(function () {
    Route::get('/{userId}',                    [UserInterestController::class, 'getUserInterests']);
    Route::post('/{userId}',                   [UserInterestController::class, 'upsertUserInterests']);
    Route::delete('/{userId}/{interestKey}',   [UserInterestController::class, 'deleteUserInterest']);
    Route::get('/{userId}/names',              [UserInterestController::class, 'getUserInterestsWithNames']);
});

// Posts
Route::prefix('posts')->group(function () {
    Route::post('/',                   [PostController::class, 'addPost']);
    Route::get('/user/{userId}',       [PostController::class, 'getUserPosts']);
    Route::get('/others/{userId}',     [PostController::class, 'getOtherUsersPosts']);
    Route::get('/skill/{skillId}',     [PostController::class, 'getPostsBySkill']);
    Route::get('/user/all/{userId}',   [PostController::class, 'getAllUserPosts']);
    Route::put('/{postId}',            [PostController::class, 'updatePost']);
    Route::delete('/{postId}',         [PostController::class, 'deletePost']);
});

// Search
Route::get('/search', [SearchController::class, 'search'])->name('search');

// Exchanges
Route::prefix('exchanges')->group(function () {
    Route::get('/user/{userId}',       [UserExchangesController::class, 'getUserExchanges']);
    Route::put('/{exchangeId}',        [UserExchangesController::class, 'updateExchange']);
    Route::delete('/{exchangeId}',     [UserExchangesController::class, 'deleteExchange']);
    Route::post('/', [UserExchangesController::class, 'addExchange']);
    Route::get('/{id}', [UserExchangesController::class, 'getExchangeById']);
});

// Reports
Route::prefix('reports')->group(function () {
    Route::get('/',            [ReportsController::class, 'getReports']);
    Route::get('/{id}',        [ReportsController::class, 'getReport']);
    Route::post('/',           [ReportsController::class, 'addReport']);
    Route::match(['put', 'patch'], '/{id}', [ReportsController::class, 'updateReport']);
    Route::delete('/{id}',     [ReportsController::class, 'deleteReport']);
});

// Requests
Route::prefix('requests')->group(function () {
    Route::get('/',                        [RequestsController::class, 'getRequests']);
    Route::get('/{id}',                    [RequestsController::class, 'getRequest']);
    Route::post('/',                       [RequestsController::class, 'addRequest']);
    Route::put('/{id}',                    [RequestsController::class, 'updateRequest']);
    Route::delete('/{id}',                 [RequestsController::class, 'deleteRequest']);
    Route::get('/requester/{userId}',      [RequestsController::class, 'getRequestsByRequester']);
    Route::get('/requested/{userId}',      [RequestsController::class, 'getRequestsToUser']);
});

//Messages
Route::prefix('messages')->controller(MessagesController::class)->group(function () {
    Route::get('/', 'getAllMessages');
    Route::get('/exchange/{exchangeId}', 'getMessagesByExchange');
    Route::get('/between/{user1}/{user2}', 'getMessagesBetweenUsers');
    Route::post('/', 'sendMessage');
    Route::patch('/{id}/status', 'updateMessageStatus');
    Route::delete('/{id}', 'deleteMessage');
    Route::get('/last/{exchangeId}', [MessagesController::class, 'getLastMessageByExchange']);
    Route::get('/unread/count/{userId}/{exchangeId}', [MessagesController::class, 'countUnreadMessages']);
    Route::put('/{id}', [MessagesController::class, 'updateMessageContent']);
});

//Sessions
Route::prefix('sessions')->controller(SessionsController::class)->group(function () {
    Route::post('/schedule', 'scheduleSession');
    Route::get('/{exchangeId}', 'getSessions');
    Route::post('/{sessionId}/validate', 'validateSession');
    Route::post('/{sessionId}/complete', 'markSessionCompleted');
});