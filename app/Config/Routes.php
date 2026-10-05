<?php
use CodeIgniter\Router\RouteCollection;
/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api', ['filter' => 'cors'], static function ($routes) {
    $routes->post('webhooks/midtrans', 'Api\Webhooks::midtrans');
});

$routes->group('api', ['filter' => 'cors'], static function ($routes) {
    $routes->post('transactions', 'Api\Transactions::create');
    $routes->get('transactions/enrollments/(:segment)', 'Api\Transactions::enrollments/$1');
    $routes->get('transactions/user/(:segment)', 'Api\Transactions::userOrders/$1');
    $routes->post('transactions/auto-expire', 'Api\Transactions::autoExpire');
    $routes->post('transactions/webhook', 'Api\Transactions::webhook');
    $routes->get('transactions/status/(:segment)', 'Api\Transactions::status/$1');
});

$routes->group('api', ['filter' => 'cors'], static function ($routes) {
    $routes->post('webhooks/midtrans', 'Api\Webhooks::midtrans');
});

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->group('users', static function ($routes) {
        $routes->get('find_by_email', 'Users::find_by_email');
        $routes->post('', 'Users::create');
        $routes->post('onboarding', 'Users::onboarding');
    });
    $routes->get('categories/tree', 'Categories::tree');
    $routes->resource('categories');
    $routes->resource('courses');
    $routes->resource('dashboard');
    $routes->get('testimonials', 'UiData::testimonials');
    $routes->get('stats', 'UiData::stats');
    $routes->get('platform-settings', 'UiData::platformSettings');
    $routes->get('coupons', 'UiData::coupons');
    $routes->get('course-detail/(:any)', 'UiData::courseDetail/$1');
});







$routes->group('api', ['namespace' => 'App\Controllers\Api', 'filter' => 'cors'], static function ($routes) {
    $routes->post('support-tickets/escalate', 'SupportTickets::escalate');
});

// =====================================================
// FASE 5: Learning / Classroom / Quiz Routes
// =====================================================
$routes->group('api', ['namespace' => 'App\Controllers\Api', 'filter' => 'cors'], static function ($routes) {
    // Classroom - GET full curriculum with user progress
    $routes->get('classroom/(:segment)', 'Classroom::show/$1');

    // Lesson Progress - POST to mark lesson complete
    $routes->post('lesson-progress', 'LessonProgress::create');

    // Quiz Routes
    $routes->get('quizzes/(:segment)',                    'Quizzes::show/$1');
    $routes->post('quizzes/(:segment)/start',             'Quizzes::start/$1');
    $routes->post('quizzes/(:segment)/submit',            'Quizzes::submit/$1');
    $routes->get('quizzes/(:segment)/result/(:segment)', 'Quizzes::result/$1/$2');
});