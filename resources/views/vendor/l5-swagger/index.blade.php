<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $documentationTitle }}</title>
    <link rel="stylesheet" type="text/css" href="{{ l5_swagger_asset($documentation, 'swagger-ui.css') }}">
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-32x32.png') }}"
        sizes="32x32" />
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-16x16.png') }}"
        sizes="16x16" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }

        /* Personnalisation BookYourCoach */
        .swagger-ui .topbar {
            background-color: #2c3e50 !important;
            border-bottom: 3px solid #3498db;
        }

        .swagger-ui .topbar .download-url-wrapper .select-label {
            color: white;
        }

        .swagger-ui .topbar .download-url-wrapper input[type=text] {
            border-color: #3498db;
        }

        .swagger-ui .info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .swagger-ui .info .title {
            color: white !important;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .swagger-ui .info .description {
            color: #ecf0f1;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .swagger-ui .info .description h2 {
            color: #f39c12 !important;
            border-bottom: 2px solid #f39c12;
            padding-bottom: 0.5rem;
        }

        .swagger-ui .opblock.opblock-post {
            border-color: #27ae60;
            background: rgba(39, 174, 96, 0.1);
        }

        .swagger-ui .opblock.opblock-post .opblock-summary-method {
            background: #27ae60;
        }

        .swagger-ui .opblock.opblock-get {
            border-color: #3498db;
            background: rgba(52, 152, 219, 0.1);
        }

        .swagger-ui .opblock.opblock-get .opblock-summary-method {
            background: #3498db;
        }

        .swagger-ui .opblock.opblock-put {
            border-color: #f39c12;
            background: rgba(243, 156, 18, 0.1);
        }

        .swagger-ui .opblock.opblock-put .opblock-summary-method {
            background: #f39c12;
        }

        .swagger-ui .opblock.opblock-delete {
            border-color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
        }

        .swagger-ui .opblock.opblock-delete .opblock-summary-method {
            background: #e74c3c;
        }

        /* Tags styling */
        .swagger-ui .opblock-tag {
            background: #2c3e50 !important;
            color: white !important;
            border-radius: 8px;
            margin-bottom: 1rem;
            padding: 1rem;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .swagger-ui .opblock-tag:hover {
            background: #34495e !important;
        }

        /* Boutons */
        .swagger-ui .btn.authorize {
            background-color: #e67e22;
            border-color: #e67e22;
            color: white;
        }

        .swagger-ui .btn.authorize:hover {
            background-color: #d35400;
            border-color: #d35400;
        }

        .swagger-ui .btn.execute {
            background-color: #27ae60;
            border-color: #27ae60;
        }

        .swagger-ui .btn.execute:hover {
            background-color: #229954;
            border-color: #229954;
        }

        /* Header personnalisé */
        .custom-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            text-align: center;
        }

        .custom-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: bold;
        }

        .custom-header p {
            margin: 0.5rem 0 0 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Environnement info */
        .env-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .env-info h4 {
            color: #495057;
            margin: 0 0 0.5rem 0;
        }

        .env-info ul {
            margin: 0;
            padding-left: 1.2rem;
        }

        .env-info ul li {
            margin-bottom: 0.3rem;
        }

        .env-info ul li strong {
            color: #007bff;
        }
    </style>
    @if (config('l5-swagger.defaults.ui.display.dark_mode'))
        <style>
            body#dark-mode,
            #dark-mode .scheme-container {
                background: #1b1b1b;
            }

            #dark-mode .scheme-container,
            #dark-mode .opblock .opblock-section-header {
                box-shadow: 0 1px 2px 0 rgba(255, 255, 255, 0.15);
            }

            #dark-mode .operation-filter-input,
            #dark-mode .dialog-ux .modal-ux,
            #dark-mode input[type=email],
            #dark-mode input[type=file],
            #dark-mode input[type=password],
            #dark-mode input[type=search],
            #dark-mode input[type=text],
            #dark-mode textarea {
                background: #343434;
                color: #e7e7e7;
            }

            #dark-mode .title,
            #dark-mode li,
            #dark-mode p,
            #dark-mode table,
            #dark-mode label,
            #dark-mode .opblock-tag,
            #dark-mode .opblock .opblock-summary-operation-id,
            #dark-mode .opblock .opblock-summary-path,
            #dark-mode .opblock .opblock-summary-path__deprecated,
            #dark-mode h1,
            #dark-mode h2,
            #dark-mode h3,
            #dark-mode h4,
            #dark-mode h5,
            #dark-mode .btn,
            #dark-mode .tab li,
            #dark-mode .parameter__name,
            #dark-mode .parameter__type,
            #dark-mode .prop-format,
            #dark-mode .loading-container .loading:after {
                color: #e7e7e7;
            }

            #dark-mode .opblock-description-wrapper p,
            #dark-mode .opblock-external-docs-wrapper p,
            #dark-mode .opblock-title_normal p,
            #dark-mode .response-col_status,
            #dark-mode table thead tr td,
            #dark-mode table thead tr th,
            #dark-mode .response-col_links,
            #dark-mode .swagger-ui {
                color: wheat;
            }

            #dark-mode .parameter__extension,
            #dark-mode .parameter__in,
            #dark-mode .model-title {
                color: #949494;
            }

            #dark-mode table thead tr td,
            #dark-mode table thead tr th {
                border-color: rgba(120, 120, 120, .2);
            }

            #dark-mode .opblock .opblock-section-header {
                background: transparent;
            }

            #dark-mode .opblock.opblock-post {
                background: rgba(73, 204, 144, .25);
            }

            #dark-mode .opblock.opblock-get {
                background: rgba(97, 175, 254, .25);
            }

            #dark-mode .opblock.opblock-put {
                background: rgba(252, 161, 48, .25);
            }

            #dark-mode .opblock.opblock-delete {
                background: rgba(249, 62, 62, .25);
            }

            #dark-mode .loading-container .loading:before {
                border-color: rgba(255, 255, 255, 10%);
                border-top-color: rgba(255, 255, 255, .6);
            }

            #dark-mode svg:not(:root) {
                fill: #e7e7e7;
            }

            #dark-mode .opblock-summary-description {
                color: #fafafa;
            }
        </style>
    @endif
</head>

<body @if (config('l5-swagger.defaults.ui.display.dark_mode')) id="dark-mode" @endif>
    <div id="swagger-ui"></div>

    <script src="{{ l5_swagger_asset($documentation, 'swagger-ui-bundle.js') }}"></script>
    <script src="{{ l5_swagger_asset($documentation, 'swagger-ui-standalone-preset.js') }}"></script>
    <script>
        window.onload = function() {
            const urls = [];

            @foreach ($urlsToDocs as $title => $url)
                urls.push({
                    name: "{{ $title }}",
                    url: "{{ $url }}"
                });
            @endforeach

            // Build a system
            const ui = SwaggerUIBundle({
                dom_id: '#swagger-ui',
                urls: urls,
                "urls.primaryName": "{{ $documentationTitle }}",
                operationsSorter: {!! isset($operationsSorter) ? '"' . $operationsSorter . '"' : 'null' !!},
                configUrl: {!! isset($configUrl) ? '"' . $configUrl . '"' : 'null' !!},
                validatorUrl: {!! isset($validatorUrl) ? '"' . $validatorUrl . '"' : 'null' !!},
                oauth2RedirectUrl: "{{ route('l5-swagger.' . $documentation . '.oauth2_callback', [], $useAbsolutePath) }}",

                requestInterceptor: function(request) {
                    request.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
                    return request;
                },

                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],

                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],

                layout: "StandaloneLayout",
                docExpansion: "{!! config('l5-swagger.defaults.ui.display.doc_expansion', 'none') !!}",
                deepLinking: true,
                filter: {!! config('l5-swagger.defaults.ui.display.filter') ? 'true' : 'false' !!},
                persistAuthorization: "{!! config('l5-swagger.defaults.ui.authorization.persist_authorization') ? 'true' : 'false' !!}",

            })

            window.ui = ui

            // Ajouter le header personnalisé après le chargement
            setTimeout(() => {
                const swaggerContainer = document.querySelector('.swagger-ui');
                if (swaggerContainer) {
                    // Créer le header personnalisé
                    const customHeader = document.createElement('div');
                    customHeader.className = 'custom-header';
                    customHeader.innerHTML = `
                    <h1>🐎 BookYourCoach API</h1>
                    <p>Plateforme complète de réservation de cours d'équitation</p>
                `;

                    // Insérer en première position
                    swaggerContainer.insertBefore(customHeader, swaggerContainer.firstChild);

                    // Ajouter les informations d'environnement
                    const envInfo = document.createElement('div');
                    envInfo.className = 'env-info';
                    envInfo.innerHTML = `
                    <h4>🔧 Informations d'environnement</h4>
                    <ul>
                        <li><strong>API Base URL:</strong> http://localhost:8081/api</li>
                        <li><strong>PHPMyAdmin:</strong> <a href="http://localhost:8082" target="_blank">http://localhost:8082</a></li>
                        <li><strong>Mode Stripe:</strong> Test (clés configurées)</li>
                        <li><strong>Base de données:</strong> MySQL (Docker)</li>
                        <li><strong>Authentification:</strong> Laravel Sanctum (Bearer Token)</li>
                        <li><strong>Tests:</strong> 114 tests passent ✅</li>
                    </ul>
                `;

                    // Insérer après le header
                    swaggerContainer.insertBefore(envInfo, customHeader.nextSibling);
                }
            }, 1000);

            @if (in_array('oauth2', array_column(config('l5-swagger.defaults.securityDefinitions.securitySchemes'), 'type')))
                ui.initOAuth({
                    usePkceWithAuthorizationCodeGrant: "{!! (bool) config('l5-swagger.defaults.ui.authorization.oauth2.use_pkce_with_authorization_code_grant') !!}"
                })
            @endif
        }
    </script>
</body>

</html>
