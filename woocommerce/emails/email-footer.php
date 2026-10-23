<?php
/** Itération 8.
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

$email = $email ?? null;

?>
																		</div>
																	</td>
																</tr>
															</table>

														</td>
													</tr>
												</table>

											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td align="center" valign="top">

									<table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer" role="presentation">
										<tr>
											<td valign="top">
												<table border="0" cellpadding="10" cellspacing="0" width="100%" role="presentation">
													<tr>
														<td colspan="2" valign="middle" id="credit">
															<?php
															$email_footer_text = get_option( 'woocommerce_email_footer_text' );

															if ( apply_filters( 'woocommerce_is_email_preview', false ) ) {
																$text_transient    = get_transient( 'woocommerce_email_footer_text' );
																$email_footer_text = false !== $text_transient ? $text_transient : $email_footer_text;
															}

                                                        if ( ! $email_footer_text || '{site_title}<br />{store_address}' === trim( $email_footer_text ) ) {
                                                            $email_footer_text = 'Chocolaterie Buissière<br />27 rue Jean Jaurès — 87000 Limoges<br /><a href="tel:+33555341044">05 55 34 10 44</a>';
                                                        }
															echo wp_kses_post(
																wpautop(
																	wptexturize(

																		apply_filters( 'woocommerce_email_footer_text', $email_footer_text, $email )
																	)
																)
															);
															?>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>

								</td>
							</tr>
						</table>
					</div>
				</td>
				<td></td>
			</tr>
		</table>
	</body>
</html>
