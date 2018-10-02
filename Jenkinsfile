#!/usr/bin/env groovy

@Library('global-pipeline@develop') _

pipeline {
    agent any
    options {
        // Configure job retention policy -
        buildDiscarder(logRotator(artifactNumToKeepStr: '25', numToKeepStr: '25', daysToKeepStr: '7', artifactDaysToKeepStr: '7'))
        ansiColor colorMapName: 'xterm'
        disableConcurrentBuilds()
    }
    environment {
        TEST_AUTH               = ''
        PROD_AUTH               = ''
        STAGE_AUTH              = ''
        GITHUB_OAUTH_TOKEN      = credentials('marello-builder')
        STAGE_HOST              = ''
        PROD_HOST               = ''
        TEST_HOST               = ''
        ANSIBLE_FORCE_COLOR     = 'true'
        PHP_BIN                 = '$(which php)'
        DEP_BIN                 = ''
        DEPLOY_STAGE_PATH       = ''
        DEPLOY_TEST_PATH        = ''
        DEPLOY_PROD_PATH        = ''
        TMP_DIR_PATH            = '~/'
        SLACK_CHANNEL           = ''
        DOCKER_APP_ROOT         = '/var/www'
        DOCKER_COMPOSE          = 'docker-compose -f docker-compose-build.yml'
        COMPOSE_PROJECT_NAME    = "MARELLO_MONO_REPO"
    }
    stages {
        stage('Building') {
            steps {
                sendNotifications 'STARTED'
                sh 'docker network prune -f'
                sh "$DOCKER_COMPOSE up -d --build"
                sh '$DOCKER_COMPOSE exec -T web bash -c "mkdir -p $DOCKER_APP_ROOT/vendor $DOCKER_APP_ROOT/web/bundles; chown -R www-data:www-data $DOCKER_APP_ROOT/vendor $DOCKER_APP_ROOT/web/bundles $DOCKER_APP_ROOT/bin;"'
                sh '$DOCKER_COMPOSE exec -u www-data -T web bash -c "COMPOSER=dev.json COMPOSER_PROCESS_TIMEOUT=3000 composer install --no-suggest --prefer-dist;"'
                sh '$DOCKER_COMPOSE exec -T web bash -c "chown -R www-data:www-data $DOCKER_APP_ROOT/vendor $DOCKER_APP_ROOT/web/bundles $DOCKER_APP_ROOT/bin;"'
            }
        }

        stage('Testing') {
            steps {
                parallel (
                    phpunit: {
                        sh '$DOCKER_COMPOSE exec -u www-data -T web bash -c "php ./bin/phpunit --color --testsuite unit"'
                    },
                    phplint: {
                        sh '$DOCKER_COMPOSE exec -u www-data -T web bash -c "php ./bin/phpcs vendor/marellocommerce/marello -p --encoding=utf-8 --extensions=php --standard=psr2 --report=checkstyle --report-file=app/logs/phpcs.xml"'
                    }
                )
            }
        }
    }
    post {
        always {
            sendNotifications currentBuild.result
        }
    }
}
