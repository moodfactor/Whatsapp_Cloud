<?php date_default_timezone_set('Africa/Cairo');

use App\Http\Controllers\Admin\CertificateTemplateController;
use App\Http\Controllers\Admin\NicknameController;
use App\Http\Controllers\AuthorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DevController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ArticlesController;
use App\Http\Controllers\JournalsController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\VersionsController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\ArticlesEnController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\ConferencesController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ReceivedEmailsController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ForgetController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\PaymentsController;
use App\Http\Controllers\InternationalTypesController;
use App\Http\Controllers\JournalsResearchesController;
use App\Http\Controllers\ConferenceCategoriesController;
use App\Http\Controllers\InternationalCreditsController;
use App\Http\Controllers\InternationalJournalsController;
use App\Http\Controllers\InternationalSpecialtiesController;
use App\Http\Controllers\InternationalPublicationOrdersController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SupportChatController;
use App\Http\Livewire\Admin\EmailCampaignComponent;
use App\Http\Livewire\Admin\EmailCampaignDetailsComponent;
use App\Http\Livewire\Admin\EmailListComponent;
use App\Http\Livewire\Admin\EmailTemplateComponent;

Route::middleware(['guest'])->group(function () {
    Route::prefix(adminPrefix())->group(function () {
        Route::get('/', [LoginController::class, 'index']);
        Route::post('login', [LoginController::class, 'login']);
        Route::get('forget-password', [ForgetController::class, 'forget']);
        Route::get('reset-password/{email_hash}', [ForgetController::class, 'reset']);
        Route::post('send-mail', [ForgetController::class, 'send_mail']);
        Route::post('update-password/{email_hash}', [ForgetController::class, 'update_password']);
    });
});
Route::prefix(adminPrefix())->middleware('CheckMarketer')->group(function () {
    Route::group(['middleware' => 'administrator'], function () {});
    Route::get('count-publication-prices', [HomeController::class, 'countPublicationPrices'])->name('admin.view.countPublicationPrices');
    Route::post('count-publication-prices', [HomeController::class, 'countPublicationPricesPost'])->name('admin.calc.countPublicationPrices');
    Route::middleware(['auth:admin'])->group(function () {

        Route::resource('authors', AuthorController::class, ['as' => 'admin']);
        Route::controller(AdminController::class)->group(function () {
            Route::get('profile', 'profile');
            Route::get('admins', 'all_admin');
            Route::get('create/admin', 'create');
            Route::get('edit/admin/{id}', 'edit')->where('id', '[0-9]+');
            Route::post('edit/admin/{id}/balance', 'update_balance')->where('id', '[0-9]+')->name('balance.update');
            Route::post('agents/admin/{admin}', 'set_agents')->where('id', '[0-9]+');
            Route::post('store/admin', 'store')->name("addNewAdmin");
            Route::post('update/admin/{id}', 'update')->where('id', '[0-9]+');
            Route::get('delete/admin/{id}', 'delete')->where('id', '[0-9]+');
            Route::post('send/admin/{id}', 'send_message')->where('id', '[0-9]+');
            Route::post('update/profile', 'update_profile');
        });
        Route::resource('nickname', NicknameController::class)->names('admin.nickname');

        Route::get('notification', function () {
            return view('admin.notifications');
        });
        Route::get('home', [HomeController::class, 'index']);

        Route::get('read-notify-withdraw/{notificationId}', [HomeController::class, 'readNotifyWithdraw'])->name('read-notify-withdraw');
        Route::post('read-notify-withdraw/{notificationId}/{action}', [HomeController::class, 'confirmWithdraw'])->name('confirm-withdraw');
        Route::get('logout', [LogoutController::class, 'logout']);
        Route::prefix('payments')->group(function () {
            Route::controller(PaymentsController::class)->group(function () {
                Route::get('', 'index');
                Route::get('completion', 'paymentsCompletion')->name('payments.admin_completion');
                Route::post('completion/{id}/{type}', 'paymentsCompletionUpdate')->name('payments.admin_completion.update');
            });
        });
        Route::prefix('invoices')->group(function () {
            Route::controller(InvoicesController::class)->group(function () {
                Route::get('', 'index');
                Route::get('update-payment-type/{invoice}/{type}', 'update_payment_type')->name('invoice.update_payment_type');
                Route::get('journals', 'indexJournals');
                Route::get('create', 'create');
                Route::get('create-journals', 'createJournals');
                Route::get('edit/{id}', 'edit');;
                Route::get('edit-journal/{id}', 'editJournal');
                Route::post('store', 'store');
                Route::post('store-journal', 'storeJournal')->name('admin.storeJournal');
                Route::post('update', 'update');
                Route::post('request-update', 'request_update');
                Route::post('confirm-request-update-invoice/{notificationId}/{action}', 'confirm_update_invoice')->name('confirm-request-update-invoice');
                Route::post('updateJournal', 'updateJournal');
                Route::post('active', 'active');
                Route::post('item/destory', 'item_destory');
                Route::delete('destroy', 'destroy');
                Route::post('item/journal_item_destory', 'journal_item_destory');

                Route::get('mark_as_paid/{id}', 'mark_as_paid');
                Route::get('request_mark_as_paid/{id}', 'request_mark_as_paid');
                Route::post('send_reminder', 'send_reminder')->name('send_reminder');
            });
        });
        Route::controller(ArticlesController::class)->group(function () {
            Route::get('articles', 'index');
            Route::get('article/create', 'create');
            Route::get('article/edit/{id}', 'edit')->where('id', '[0-9]+');
            Route::post('article/status', 'status');
            Route::post('article/store', 'store');
            Route::post('article/update', 'update');
            Route::delete('article/destroy', 'destroy');
        });
        Route::resource('certificate-template', CertificateTemplateController::class);
        Route::get('certificate-template/{certificateTemplate}/input', [CertificateTemplateController::class, 'input_view'])->name('certificate-template.input');
        Route::post('certificate-template/{certificateTemplate}/input', [CertificateTemplateController::class, 'input_save'])->name('certificate-template.input.save');
        Route::post('confirm-certificate-input/{notificationId}/{action}', [CertificateTemplateController::class, 'confirmRequestCertificateInput'])->name('confirm-certificate-input');
        Route::prefix('en')->group(function () {
            Route::controller(ArticlesEnController::class)->group(function () {
                Route::get('articles', 'index');
                Route::get('article/create', 'create');
                Route::get('article/edit/{id}', 'edit')->where('id', '[0-9]+');
                Route::post('article/status', 'status');
                Route::post('article/store', 'store');
                Route::post('article/update', 'update');
                Route::delete('article/destroy', 'destroy');
            });
        });
        Route::prefix('conference')->group(function () {
            Route::controller(ConferenceCategoriesController::class)->group(function () {
                Route::get('categories', 'index');
                Route::post('category/store', 'store');
                Route::post('category/update', 'update');
                Route::post('category/destroy', 'destroy');
                Route::delete('destroy', 'conference_destroy');
            });
            Route::controller(ConferencesController::class)->group(function () {
                Route::get('all', 'index');
                Route::get('show/{id}', 'show');
                Route::post('send/certificate', 'send_certificate');
            });
        });
        Route::prefix('users')->group(function () {
            Route::get('subscribers/templates', EmailTemplateComponent::class);
            Route::get('subscribers/email-lists', EmailListComponent::class);
            Route::get('subscribers/email-campaigns', EmailCampaignComponent::class)->name('email_campaigns');
            Route::get('subscribers/email-campaigns/show/{campaign}', EmailCampaignDetailsComponent::class)->name('email_camaign_details');
            Route::controller(UsersController::class)->group(function () {
                Route::get('user-researches/create', 'admin_create_research')->name('admin_create_research');
                Route::post('admin-user-researches/store', 'admin_store_research')->name('admin_store_research');
                Route::get('', 'index');
                Route::get('subscribers/restore', 'RestoreSubscribers')->name('subscriber.restore');

                // Subscribers
                Route::get('subscribers', 'subscribers')->name('subscribers-list');
                Route::post('subscribers', 'AddSubscribers')->name('add.subscribers');
                Route::get('subscribers/new', 'newSubscriberForm')->name('new-subscriber-form');
                Route::get('subscribers/email', 'emailSubscriberForm')->name('email-form');

                // Route::get('subscribers/email-lists', 'email_lists')->name('email-form');
                // Route::get('subscribers/templates', 'templates')->name('email-form');
                // Route::get('subscribers/email-campaigns', 'email_campaigns')->name('email-form');

                Route::post('send/subscribers/email', 'SendMail')->name('subscribers.send.email');
                Route::post('subscribers/test-email', 'SendTestMail')->name('send-test-email');
                Route::get('subscribers/ajax', 'Ajaxsubscribers')->name("subscribers");
                Route::get('subscribers/remove/{email}', 'RemoveSubscribers');
                Route::post('subscriber/edit', 'EditSubscriber')->name('subscriber.edit');
                Route::post('subscriber/destroy', 'destroySubscriber')->name('subscriber.destroy');

                Route::get('show/{id}', 'show');
                Route::get('admin_verifies_user/{id}', 'admin_verifies_user');
                Route::post('status', 'update_status');
                Route::post('status', 'update_status');
                Route::post('confirm-request-ban-user/{notificationId}/{action}', [UsersController::class, 'confirmRequestBanUser'])->name('confirm-request-ban-user');
                Route::delete('delete', 'destroy');
                Route::get('researches', 'researches');
                Route::post('send_link_facture/', 'send_facture')->name('send_facture');
                Route::post('refuse/international_publication_orders', 'RefusedInternationalPublicationOrders')->name('ChangeInternationalPublicationOrders');
                Route::post('accept/international_publication_orders', 'AcceptInternationalPublicationOrders')->name('AcceptInternationalPublicationOrders');

                // baik
                Route::get('user-researches', 'user_researches')->name('admin_user_researches');
                Route::get('get-user-researches', 'get_user_researches')->name('admin_get_user_researches');
                Route::get('user-researches/{id}', 'user_researches_cat');
                Route::get('user-researches/{id}/details', 'user_researche_details')->name('research_details');
                Route::post('user-researches/{id}/publish', 'user_researche_publish')->name('research_publish');
                Route::get('user-researches/{id}/edit', 'admin_edit_research')->name('admin_edit_research');
                Route::post('user-researches/{id}/update', 'admin_update_research')->name('admin_update_research');
                Route::delete('user-researches/destroy', 'user_researches_destroy');
                Route::get('user-researches/edit/{value}/{id}', 'edit_researches');
                Route::post('chat/store', 'chat_store');
                Route::get('chat/{id}', 'chat');
                Route::post('read-notify-action-users-researches/{notificationId}/{action}', 'confirmRequestActionInUsersResearches')->name('confirm-request-action-users-researches');
                Route::post('user-researches/{userResearch}/tranfer', 'transferUserResearch')->name('transferUserResearch');
                //baik
                Route::delete('researches/destroy', 'researches_destroy');
                Route::get('support/chat/{message_id}', 'ViewSupportChat')->name('ViewSupportChat');
                Route::post('adminSendMessage', [SupportChatController::class, 'adminSendMessage'])->name('adminSendMessage');
                Route::get('supportchat', [SupportChatController::class, 'SupportChat'])->name('supportchat');
                Route::get('OpenChat/{email}', [SupportChatController::class, 'OpenChat'])->name('OpenChat');
                Route::post('support/chat/transfer', [SupportChatController::class, 'TransferChat'])->name('TransferChat');
            });
        });
        Route::controller(DocumentController::class)->prefix('documents')->group(
            function () {
                Route::get('', 'index')->name('admin.documents');
                Route::post('store', 'store')->name('admin.store_documents');
                Route::post('delete_document', 'destroy')->name('admin.delete_document');
                Route::post('update_document', 'update')->name('admin.update_document');
                Route::post('get_user_researches', 'get_user_researches')->name('admin.get_user_researches');
                Route::post('get_documents', 'get_documents')->name('admin.get_documents');
            }
        );
        Route::prefix('journals')->group(function () {
            Route::controller(JournalsController::class)->group(function () {
                Route::get('', 'index');
                Route::get('create', 'create');
                Route::get('edit/{id}', 'edit');
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::delete('destroy', 'destroy');
            });
        });
        Route::prefix('books')->group(function () {
            Route::controller(\App\Http\Controllers\BooksController::class)->group(function () {
                Route::get('', 'index');
                Route::get('create', 'create');
                Route::get('edit/{id}', 'edit');
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::delete('destroy', 'destroy');
            });
        });
        Route::prefix('researches')->group(function () {
            Route::controller(JournalsResearchesController::class)->group(function () {
                Route::get('', 'index');
                Route::get('create', 'create');
                Route::get('edit/{id}', 'edit');
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::delete('destroy', 'destroy');
            });
        });
        Route::prefix('versions')->group(function () {
            Route::controller(VersionsController::class)->group(function () {
                Route::get('', 'index');
                Route::get('create', 'create');
                Route::get('edit/{id}', 'edit');
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::delete('destroy', 'destroy');
            });
        });
        Route::prefix('services')->group(function () {
            Route::controller(ServicesController::class)->group(function () {
                Route::get('', 'index');
                Route::get('create', 'create');
                Route::get('edit/{id}', 'edit');
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::delete('destroy', 'destroy');
            });
        });
        Route::prefix('team')->group(function () {
            Route::controller(TeamController::class)->group(function () {
                Route::get('', 'index');
                Route::get('create', 'create');
                Route::get('edit/{id}', 'edit');
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::delete('destroy', 'destroy');
            });
        });
        Route::prefix('international')->group(function () {
            Route::controller(InternationalCreditsController::class)->group(function () {
                Route::get('', 'index');
                Route::post('store', 'store');
                Route::delete('destroy', 'destroy');
            });
        });
        Route::prefix('faqs')->group(function () {
            Route::controller(FaqController::class)->group(function () {
                Route::get('', 'index');
                Route::get('create', 'create');
                Route::get('edit/{id}', 'edit');
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::delete('destroy', 'destroy');
            });
        });
        Route::prefix('international-publishing')->group(function () {
            Route::controller(InternationalTypesController::class)->group(function () {
                Route::get('types-of-publication', 'index');
                Route::prefix('types')->group(function () {
                    Route::post('store', 'store');
                    Route::delete('destroy', 'destroy');
                    Route::post('update', 'update');
                });
            });
            Route::controller(InternationalSpecialtiesController::class)->group(function () {
                Route::prefix('specialties')->group(function () {
                    Route::get('', 'index');
                    Route::post('store', 'store');
                    Route::delete('destroy', 'destroy');
                    Route::post('update', 'update');
                });
            });
            Route::controller(InternationalJournalsController::class)->group(function () {
                Route::prefix('journals')->group(function () {
                    Route::get('', 'index');
                    Route::post('store', 'store');
                    Route::delete('destroy', 'destroy');
                    Route::post('update', 'update');
                });
            });
            Route::controller(InternationalPublicationOrdersController::class)->group(function () {
                Route::delete('destroy', 'admin_destroy');
                Route::prefix('orders')->group(function () {
                    Route::get('', 'orders');
                    Route::get('show/{id}', 'show');
                });
            });
        });
        Route::prefix('settings')->group(function () {
            Route::controller(SettingsController::class)->group(function () {
                Route::get('', 'index');
                Route::post('social/create', 'social_store');
                Route::post('social/update', 'social_update');
                Route::post('mail/create', 'mail_store');
                Route::post('mail/update', 'mail_update');
                Route::post('phone/create', 'phone_store');
                Route::post('phone/update', 'phone_update');
                Route::post('alert_in_chat/update', 'alert_in_chat_update');
                Route::post('front_sections/update', 'front_sections_update');
                Route::post('study_submition_sections/update', 'study_submition_sections_update');
                Route::post('journals_statuses/update', 'updateJournalsStatus');
                Route::get('email_confirmation_alerts', 'email_confirmation_alerts_page');
                Route::post('email_confirmation_alerts', 'email_confirmation_alerts');
            });
            Route::controller(ReceivedEmailsController::class)->group(function () {
                Route::post('emails/create', 'store');
                Route::delete('emails/destroy', 'destroy');
            });
        });
    });
    Route::middleware(['role:agent'])->group(function () {
        Route::get('certificate-template/{certificateTemplate}/input', [CertificateTemplateController::class, 'input_view'])->name('certificate-template.input');

        Route::post('certificate-template/{certificateTemplate}/input', [CertificateTemplateController::class, 'input_save'])->name('certificate-template.input.save');

        Route::resource('authors', AuthorController::class, ['as' => 'admin'])->except('show');
        Route::get('home', [HomeController::class, 'index']);
        Route::post('withdraw', [HomeController::class, 'withdraw'])->name('withdraw');
        Route::controller(AdminController::class)->group(function () {
            Route::get('profile', 'profile');
            Route::post('update/profile', 'update_profile');
        });
        Route::prefix('payments')->group(function () {
            Route::controller(PaymentsController::class)->group(function () {
                Route::get('', 'index');
            });
        });
        Route::prefix('invoices')->group(function () {
            Route::controller(InvoicesController::class)->group(function () {
                Route::get('', 'index');
                Route::get('create', 'create');
                Route::post('store', 'store');
                Route::post('active', 'active');
                Route::post('item/destory', 'item_destory');
            });
        });
        Route::controller(DocumentController::class)->prefix('documents')->group(
            function () {
                Route::get('', 'index')->name('admin.documents');
            }
        );
        Route::prefix('users')->group(function () {
            Route::controller(UsersController::class)->group(function () {
                Route::get('user-researches/create', 'admin_create_research')->name('admin_create_research');
                Route::post('admin-user-researches/store', 'admin_store_research')->name('admin_store_research');
                // baik
                Route::get('user-researches', 'user_researches')->name('admin_user_researches');
                Route::get('user-researches/{id}', 'user_researches_cat');
                Route::get('user-researches/{id}/details', 'user_researche_details')->name('research_details');
                Route::get('user-researches/{id}/edit', 'admin_edit_research')->name('admin_edit_research');
                Route::post('user-researches/{id}/update', 'admin_update_research')->name('admin_update_research');
                Route::delete('user-researches/destroy', 'user_researches_destroy');
                Route::get('user-researches/edit/{value}/{id}', 'edit_researches');
                Route::get('user-researches/read-agent-create-demande-notification/{notificationId}', 'read_agent_create_demande_notification')->name('read_agent_create_demande_notification');
                Route::get('read-notify-request-action-users-researches/{notificationId}', 'readRequestActionInUsersResearches')->name('read-action-request-users-researches');
                Route::post('request-set-certificate/{id}', 'requestSetCertificate')->name('request-set-certificate');
                Route::post('confirm-set-certificate/{notificationId}/{action}', 'confirmRequestSetCertificate')->name('confirm-request-set-certificate');

                Route::get('show/{id}', 'show')->name('show');

                Route::post('adminSendMessage', [SupportChatController::class, 'adminSendMessage'])->name('adminSendMessage');
                Route::get('support/chat/{message_id}', 'ViewSupportChat')->name('ViewSupportChat');
                Route::get('supportchat', [SupportChatController::class, 'SupportChat'])->name('supportchat');
                Route::get('OpenChat/{email}', [SupportChatController::class, 'OpenChat'])->name('OpenChat');
                Route::post('confirm-request-transer-chat-support/{notificationId}/{action}', [SupportChatController::class, 'confirmRequestTranserChatSupport'])->name('confirm-request-transer-chat-support');
            });
        });
        Route::get('notification', function () {
            return view('admin.notifications');
        });
        Route::get('read-notify-withdraw/{notificationId}', [HomeController::class, 'readNotifyWithdraw'])->name('read-notify-withdraw');
        Route::get('logout', [LogoutController::class, 'logout']);
    });
    Route::middleware(['role:editor'])->group(function () {
        Route::resource('authors', AuthorController::class, ['as' => 'admin'])->except('show');
        Route::get('home', [HomeController::class, 'index']);
        Route::controller(UsersController::class)->group(function () {
            Route::post('user-researches/{id}/editing', 'user_researche_editing')->name('research_editing');
        });

        Route::get('notification', function () {
            return view('admin.notifications');
        });
        Route::get('read-notify-withdraw/{notificationId}', [HomeController::class, 'readNotifyWithdraw'])->name('read-notify-withdraw');
        Route::post('confirm-request-edit-photo/{notificationId}/{action}', [HomeController::class, 'confirm_request_edit_photo'])->name('confirm-request-edit-photo');
        Route::post('user_researche-request-editing/{notificationId}/{action}', [UsersController::class, 'user_researche_request_editing'])->name('confirm-request-update-file-research');;
        Route::post('user_researche-request-publish/{notificationId}/{action}', [UsersController::class, 'user_researche_request_publish'])->name('confirm-request-publish-research');
        Route::post('invoice-paid-request/{notificationId}/{action}', [InvoicesController::class, 'invoice_paid_request'])->name('confirm-request-paid-invoice');
        Route::get('logout', [LogoutController::class, 'logout']);
    });
    Route::middleware(['role:marketing_manager'])->group(function () {
        Route::get('home', [HomeController::class, 'index']);

        Route::get('notification', function () {
            return view('admin.notifications');
        });

        Route::get('logout', [LogoutController::class, 'logout']);
    });
    Route::middleware(['role:designer'])->group(function () {
        Route::get('home', [HomeController::class, 'index']);
        Route::controller(ArticlesController::class)->group(function () {
            Route::get('articles', 'index');
            Route::get('article/edit/{id}', 'edit')->where('id', '[0-9]+');
            Route::post('article/update', 'update');
        });
        Route::prefix('en')->group(function () {
            Route::controller(ArticlesEnController::class)->group(function () {
                Route::get('articles', 'index');
                Route::get('article/edit/{id}', 'edit')->where('id', '[0-9]+');
                Route::post('article/update', 'update');
            });
        });
        Route::prefix('journals')->group(function () {
            Route::controller(JournalsController::class)->group(function () {
                Route::get('', 'index');
                Route::get('edit/{id}', 'edit');
                Route::post('update', 'update');
            });
        });

        Route::get('notification', function () {
            return view('admin.notifications');
        });

        Route::get('logout', [LogoutController::class, 'logout']);
    });
    Route::get('read-notify/{notificationId}', [HomeController::class, 'readNotify'])->name('read-notify');

        // Conversations routes - accessible by administrator, agent, and supervisor
        Route::middleware(['role:administrator,agent,supervisor'])->group(function () {
            Route::get('/conversations', [\App\Http\Controllers\ConversationController::class, 'index'])->name('admin.conversations.index');
            Route::get('/conversations/{conversation}', [\App\Http\Controllers\ConversationController::class, 'show'])->name('admin.conversations.show');
        });
});

Route::get('hosam', function () {
    return view('admin.SubscriberMail');
});
