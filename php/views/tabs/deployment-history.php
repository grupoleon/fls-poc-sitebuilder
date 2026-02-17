                    <div id="deployment-history-content" class="tab-content">
                        <div class="page-header">
                            <h2>Deployment History</h2>
                            <p class="text-muted">View all deployments grouped by site domain</p>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h2 class="card-title mb-0">All Deployments</h2>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="refresh-deployments-btn" onclick="window.adminInterface?.loadAllDeployments()">
                                    <i class="fas fa-sync-alt me-1"></i> Refresh
                                </button>
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
