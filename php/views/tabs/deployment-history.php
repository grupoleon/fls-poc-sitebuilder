                    <div id="deployment-history-content" class="tab-content">
                        <div class="page-header">
                            <h2>Deployment History</h2>
                            <p class="text-muted">View all deployments grouped by site domain</p>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h2 class="card-title mb-0">All Deployments</h2>
                                <div>
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2" id="refresh-deployments-btn" onclick="window.adminInterface?.loadAllDeployments()">
                                        <i class="fas fa-sync-alt me-1"></i> Refresh
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="update-deployments-list-btn" onclick="window.adminInterface?.refreshDeploymentList()">
                                        <i class="fas fa-check-circle me-1"></i> Update List
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="deployments-list">
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-spinner fa-spin me-2"></i> Loading deployment history...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
