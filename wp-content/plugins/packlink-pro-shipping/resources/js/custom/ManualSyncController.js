if (!window.Packlink) {
	window.Packlink = {};
}

(function () {
	/**
	 * @param {{getUrl: string, submitUrl: string}} configuration
	 * @constructor
	 */
	function ManualSyncController(configuration) {
		const templateService = Packlink.templateService,
			ajaxService = Packlink.ajaxService,
			utilityService = Packlink.utilityService,
			state = Packlink.state,
			templateId = 'pl-manual-sync-page';

		/**
		 * Sets initial checkbox value and add event listener
		 *
		 * @param {{manual_sync_status: boolean}} response
		 */
		const setManualSyncStatus = (response) => {
			const checkbox = templateService.getMainPage().querySelector('#pl-manual-sync')

			checkbox.checked = response.manual_sync_status;
			checkbox.addEventListener('click', () => {
				ajaxService.post(configuration.submitUrl, {'manual_sync_status': checkbox.checked});
			});

			utilityService.hideSpinner();
		};

		/**
		 * Displays page content.
		 */
		this.display = () => {
			templateService.setCurrentTemplate(templateId);
			const backButton = templateService.getMainPage().querySelector('.pl-sub-header button');

			backButton.addEventListener('click', () => {
				state.goToState('configuration');
			});

			ajaxService.get(configuration.getUrl, (response) => {
				setManualSyncStatus(response);
			});
		};
	}

	Packlink.ManualSyncController = ManualSyncController;
})();
