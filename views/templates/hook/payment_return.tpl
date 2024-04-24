{if $status == 'ok'}
	<p>Cliquez sur le bouton pour etre rediriger vers whatsapp. <br/><a aria-label="Chat on WhatsApp" href="https://wa.me/{$checkAddress}?text=bonjour%20{$shop_name}.%20Ma%20commande%20a%20pour%20reference%20:%20{$reference}%20et%20a%20un%20cout%20de%20:%20{$total_to_pay}" target="blank"><img alt="Chat on WhatsApp" src="../modules/whatsapp_payment/WhatsAppButton.png" height="60"/><a /></p>
  
	<p>
		{l s='Your order on %s is complete.' sprintf=[$shop_name] d='Modules.Checkpayment.Shop'}
	</p>

	<p>
		{l s='Veuillez continuer votre paiement via whatsapp:' d='Modules.Checkpayment.Shop'}
	</p>

	<ul>
		<li>
			{l s='Payment amount.' d='Modules.Checkpayment.Shop'}
			<span class="price"><strong>{$total_to_pay}</strong></span>
		</li>

		<li>
			{l s='Payable to the order of' d='Modules.Checkpayment.Shop'}
			<strong>{if $manager_name}{$manager_name}{else}___________{/if}</strong>
		</li>

		<li>
			{l s='Mail to' d='Modules.Checkpayment.Shop'}
			<strong>{if $checkAddress}{$checkAddress nofilter}{else}___________{/if}</strong>
		</li>

		{if !isset($reference)}
			<li>
				{l s='Do not forget to insert your order number #%d.' sprintf=[$id_order] d='Modules.Checkpayment.Shop'}
			</li>
		{else}
			<li>
				{l s='Do not forget to insert your order reference %s.' sprintf=[$reference] d='Modules.Checkpayment.Shop'}
			</li>
		{/if}
	</ul>

	<p>
		{l s='An email has been sent to you with this information.' d='Modules.Checkpayment.Shop'}
	</p>

	<p>
		<strong>{l s='Your order will be sent as soon as we receive your payment.' d='Modules.Checkpayment.Shop'}</strong>
	</p>

	<p>
		{l s='For any questions or for further information, please contact our' d='Modules.Checkpayment.Shop'}
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='customer service department.' d='Modules.Checkpayment.Shop'}</a>.
	</p>
{else}
	<p class="warning">
		{l s='We have noticed that there is a problem with your order. If you think this is an error, you can contact our' d='Modules.Checkpayment.Shop'}
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='customer service department.' d='Modules.Checkpayment.Shop'}</a>.
	</p>
{/if}
