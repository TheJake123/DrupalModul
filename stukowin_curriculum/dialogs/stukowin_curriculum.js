/**
 * The stukowin_curriculum dialog definition.
 * 
 */

// Our dialog definition.
CKEDITOR.dialog
		.add(
				'stukowin_curriculum_Dialog',
				function(editor) {
					return {
						title : 'Insert a Curriculum View',
						minWidth : 400,
						minHeight : 200,
						contents : [ {
							id : 'currdialog',
							elements : [
									{
										type : 'radio',
										id : 'currtype',
										label : 'Select the curricula type you want to display:',
										items : [
												[ 'Bachelor', 'Bachelorstudium' ],
												[ 'Master', 'Masterstudium' ] ],
										'default' : 'Bachelorstudium'
									}, {
										type : 'fieldset',
										label : 'Taxonomy types to display',
										children : [ {
											type : 'vbox',
											padding : 0,
											children : [ {
												type : 'checkbox',
												id : 'normal',
												label : 'Normal',
												'default' : 'checked'
											}, {
												type : 'checkbox',
												id : 'itsv',
												label : 'ITSV'
											}, {
												type : 'checkbox',
												id : 'specialisation',
												label : 'Specialisation'
											} ]
										} ]
									} ]
						} ],
						// This method is invoked once a user clicks the OK
						// button, confirming
						// the dialog.
						onOk : function() {

							// The context of this function is the dialog object
							// itself.
							// http://docs.ckeditor.com/#!/api/CKEDITOR.dialog
							var dialog = this;
							var drupal_root = Drupal.settings.basePath;

							// Creates a new <div> element.
							var div = editor.document.createElement('div');

							// Set element attribute and text, by getting the
							// defined field
							// values.
							var currType = dialog.getValueOf('currdialog',
									'currtype');
							var taxonomyTypeSet = false;
							var curriculums = "";
							if (dialog.getValueOf('currdialog', 'normal')) {
								curriculums += 'curriculum ';
								taxonomyTypeSet = true;
							}
							if (dialog.getValueOf('currdialog', 'itsv')) {
								curriculums += 'itsv ';
								taxonomyTypeSet = true;
							}
							if (dialog.getValueOf('currdialog',
									'specialisation')) {
								curriculums += 'specialisation';
								taxonomyTypeSet = true;
							}
							if (!taxonomyTypeSet) {
								alert('Error: Please select at least one taxonomy type to display');
								return;
							}
							div.setAttribute('data-currtype', currType);
							div.setAttribute('data-curriculums', curriculums);
							div.setAttribute('id', "curriculum_display");

							// Creates the explanation for the curriculum display
							var curr_div = document.createElement('div');
							curr_div.setAttribute('id', 'curriculum_legende')
							var table = editor.document.createElement('table');
							table.id = "Legende";

							var row1 = table.insertRow(0);
							var cell11 = row1.insertCell(0);
							var cell12 = row1.insertCell(-1);
							var cell13 = row1.insertCell(-1);
							var cell14 = row1.insertCell(-1);

							var row2 = table.insertRow(-1);
							var cell21 = row2.insertCell(0);
							var cell22 = row2.insertCell(-1);
							var cell31 = row2.insertCell(-1);
							var cell32 = row2.insertCell(-1);

							var cell23 = row1.insertCell(-1);
							var cell24 = row1.insertCell(-1);
							var cell33 = row2.insertCell(-1);
							var cell34 = row2.insertCell(-1);

							var plusIcon = document.createElement('img');
							plusIcon.src = drupal_root
									+ "sites/all/modules/stukowin/images/Plus.png";

							var minusIcon = document.createElement('img');
							minusIcon.src = drupal_root
									+ "sites/all/modules/stukowin/images/Minus.png";

							var vorIcon = document.createElement('img');
							vorIcon.src = drupal_root
									+ "sites/all/modules/stukowin/images/Voraussetzung.png";

							var empfIcon = document.createElement('img');
							empfIcon.src = drupal_root
									+ "sites/all/modules/stukowin/images/Empfohlen.png";

							var ectsIcon = document.createElement('img');
							ectsIcon.src = drupal_root
									+ "sites/all/modules/stukowin/images/ECTS.png";

							var voIcon = document.createElement('img');
							voIcon.src = drupal_root
									+ "sites/all/modules/stukowin/images/V300.png";

							cell11.appendChild(plusIcon);
							cell12.innerHTML = "Aufklappen";
							cell13.appendChild(empfIcon);
							cell14.innerHTML = "Empfohlene Voraussetzungen anzeigen";

							cell21.appendChild(minusIcon);
							cell22.innerHTML = "Zuklappen";
							cell23.appendChild(ectsIcon);
							cell24.innerHTML = "ECTS";

							cell31.appendChild(vorIcon);
							cell32.innerHTML = "Verpflichtende Voraussetzungen anzeigen";
							cell33.appendChild(voIcon);
							cell34.innerHTML = "LVA-Typ";
							div.appendChild(curr_div);
							curr_div.appendChild(table);

							editor.insertElement(div);
						}
					};
				});
