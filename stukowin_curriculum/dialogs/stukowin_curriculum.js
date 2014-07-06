/**
 * The stukowin_curriculum dialog definition.
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
							// Creates the explanation for the curriculum
							// display
							var curr_div = new CKEDITOR.dom.element('div');
							curr_div.setAttribute('id', 'curriculum_legende')
							curr_div.setStyle('padding-left', '1.5em');
							var table = new CKEDITOR.dom.element('table');
							table.id = "Legende";

							var row1 = new CKEDITOR.dom.element('tr');
							table.append(row1);

							var cell11 = new CKEDITOR.dom.element('td');
							row1.append(cell11);
							var cell12 = new CKEDITOR.dom.element('td');
							row1.append(cell12);
							var cell13 = new CKEDITOR.dom.element('td');
							row1.append(cell13);
							var cell14 = new CKEDITOR.dom.element('td');
							row1.append(cell14);

							var row2 = new CKEDITOR.dom.element('tr');
							table.append(row2);
							var cell21 = new CKEDITOR.dom.element('td');
							row2.append(cell21);
							var cell22 = new CKEDITOR.dom.element('td');
							row2.append(cell22);
							var cell31 = new CKEDITOR.dom.element('td');
							row2.append(cell31);
							var cell32 = new CKEDITOR.dom.element('td');
							row2.append(cell32);

							var cell23 = new CKEDITOR.dom.element('td');
							row1.append(cell23);
							var cell24 = new CKEDITOR.dom.element('td');
							row1.append(cell24);
							var cell33 = new CKEDITOR.dom.element('td');
							row2.append(cell33);
							var cell34 = new CKEDITOR.dom.element('td');
							row2.append(cell34);

							var plusIcon = new CKEDITOR.dom.element('img');
							plusIcon
									.setAttribute(
											'src',
											drupal_root
													+ "sites/all/modules/stukowin/images/Plus.png");
							plusIcon.setStyle('width', '3em');
							plusIcon.setStyle('height', '3em');

							var minusIcon = new CKEDITOR.dom.element('img');
							minusIcon
									.setAttribute(
											'src',
											drupal_root
													+ "sites/all/modules/stukowin/images/Minus.png");
							minusIcon.setStyle('width', '3em');
							minusIcon.setStyle('height', '3em');

							var vorIcon = new CKEDITOR.dom.element('img');
							vorIcon
									.setAttribute(
											'src',
											drupal_root
													+ "sites/all/modules/stukowin/images/Voraussetzung.png");
							vorIcon.setStyle('width', '3em');
							vorIcon.setStyle('height', '3em');

							var empfIcon = new CKEDITOR.dom.element('img');
							empfIcon
									.setAttribute(
											'src',
											drupal_root
													+ "sites/all/modules/stukowin/images/Empfohlen.png");
							empfIcon.setStyle('width', '3em');
							empfIcon.setStyle('height', '3em');

							var ectsIcon = new CKEDITOR.dom.element('img');
							ectsIcon
									.setAttribute(
											'src',
											drupal_root
													+ "sites/all/modules/stukowin/images/ECTS.png");
							ectsIcon.setStyle('width', '3em');
							ectsIcon.setStyle('height', '3em');

							var voIcon = new CKEDITOR.dom.element('img');
							voIcon
									.setAttribute(
											'src',
											drupal_root
													+ "sites/all/modules/stukowin/images/V300.png");
							voIcon.setStyle('width', '3em');
							voIcon.setStyle('height', '3em');

							cell11.append(plusIcon);
							cell12.appendText("Aufklappen");
							cell13.append(empfIcon);
							cell14
									.appendText("Empfohlene Voraussetzungen anzeigen");

							cell21.append(minusIcon);
							cell22.appendText("Zuklappen");
							cell23.append(ectsIcon);
							cell24.appendText("ECTS");

							cell31.append(vorIcon);
							cell32
									.appendText("Verpflichtende Voraussetzungen anzeigen");
							cell33.append(voIcon);
							cell34.appendText("LVA-Typ");
							div.append(curr_div);
							curr_div.append(table);

							editor.insertElement(div);
						}
					};
				});
