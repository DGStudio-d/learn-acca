// Jenkins Job DSL script for GitHub webhook integration
multibranchPipelineJob('laravel-app-pipeline') {
    displayName('Laravel Application CI/CD Pipeline')
    description('Automated CI/CD pipeline for Laravel application with GitHub integration')
    
    branchSources {
        github {
            id('github-source')
            scanCredentialsId('github-credentials')
            repoOwner('your-organization')
            repository('your-laravel-app')
            
            // Configure branch discovery
            buildOriginBranch(true)
            buildOriginBranchWithPR(true)
            buildOriginPRMerge(false)
            buildOriginPRHead(true)
            buildForkPRMerge(true)
            buildForkPRHead(false)
            
            // Configure webhook triggers
            configure { node ->
                def traits = node / 'source' / 'traits'
                
                // Add webhook trigger trait
                traits << 'org.jenkinsci.plugins.github__branch__source.WebhookRegistrationTrait' {
                    mode('ITEM')
                }
                
                // Add status notification trait
                traits << 'org.jenkinsci.plugins.github__branch__source.StatusNotificationTrait' {
                    name('')
                    contexts {
                        'org.jenkinsci.plugins.github__branch__source.StatusNotificationTrait_-StatusNotificationContext' {
                            context('jenkins/ci')
                            typeSuffix(true)
                        }
                    }
                    suppressUpToDate(false)
                }
                
                // Add pull request comment trigger
                traits << 'org.jenkinsci.plugins.github__branch__source.TriggerPRCommentBranchProperty' {
                    commentBody('jenkins test this please')
                }
            }
        }
    }
    
    // Configure branch filtering
    configure { node ->
        def traits = node / 'sources' / 'data' / 'jenkins.branch.BranchSource' / 'source' / 'traits'
        traits << 'jenkins.scm.impl.trait.WildcardSCMHeadFilterTrait' {
            includes('main develop feature/* hotfix/* release/*')
            excludes('')
        }
    }
    
    // Orphaned item strategy
    orphanedItemStrategy {
        discardOldItems {
            daysToKeep(7)
            numToKeep(10)
        }
    }
    
    // Periodic folder trigger
    triggers {
        periodicFolderTrigger {
            interval('15m')
        }
    }
    
    // Factory configuration
    factory {
        workflowBranchProjectFactory {
            scriptPath('Jenkinsfile')
        }
    }
}

// Create separate job for PR validation
multibranchPipelineJob('laravel-app-pr-validation') {
    displayName('Laravel Application PR Validation')
    description('Pull request validation pipeline for Laravel application')
    
    branchSources {
        github {
            id('github-pr-source')
            scanCredentialsId('github-credentials')
            repoOwner('your-organization')
            repository('your-laravel-app')
            
            // Only build PRs for validation
            buildOriginBranch(false)
            buildOriginBranchWithPR(false)
            buildOriginPRMerge(false)
            buildOriginPRHead(true)
            buildForkPRMerge(false)
            buildForkPRHead(true)
            
            configure { node ->
                def traits = node / 'source' / 'traits'
                
                // Add webhook trigger for PRs
                traits << 'org.jenkinsci.plugins.github__branch__source.WebhookRegistrationTrait' {
                    mode('ITEM')
                }
                
                // Add PR status notification
                traits << 'org.jenkinsci.plugins.github__branch__source.StatusNotificationTrait' {
                    name('')
                    contexts {
                        'org.jenkinsci.plugins.github__branch__source.StatusNotificationTrait_-StatusNotificationContext' {
                            context('jenkins/pr-validation')
                            typeSuffix(true)
                        }
                    }
                    suppressUpToDate(false)
                }
            }
        }
    }
    
    // Factory configuration for PR pipeline
    factory {
        workflowBranchProjectFactory {
            scriptPath('Jenkinsfile.pr')
        }
    }
}

// Configure GitHub webhook globally
configure { root ->
    def globalConfig = root / 'hudson.plugins.git.GitSCM_-DescriptorImpl'
    globalConfig / 'globalConfigName' << 'Jenkins CI'
    globalConfig / 'globalConfigEmail' << 'jenkins@your-domain.com'
    
    // GitHub plugin configuration
    def githubConfig = root / 'org.jenkinsci.plugins.github.config.GitHubPluginConfig'
    githubConfig / 'hookUrl' << 'https://your-jenkins.com/github-webhook/'
    githubConfig / 'useJackson2' << 'true'
}