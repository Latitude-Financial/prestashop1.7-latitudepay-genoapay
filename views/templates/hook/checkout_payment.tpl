{if $currency_code === 'NZD'}
    <div class="g-payment-info">
        <div style="margin:10px 0px;">
            <span style="font-weight: 700; line-height: 24px;">{l s="Shop now pay later."}</span>
            <a id="genoapay-popup" href="javascript:void(0)" target="_blank" style="text-decoration: underline; marigin-left: 5px; color: {$branding_color}">
                <span style="font-size: 11px;">{l s="Learn More"}</span>
            </a>
        </div>

        <p style="margin-bottom: 10px;">{l s="10 weekly payments from "}<strong style="color:{$branding_color}">{$splited_payment}</strong></p>

        <p style="font-size: 22px; color:{$branding_color}; font-weight: 600; margin-bottom: 20px">{l s="No interest." mod="latitude_official" }<span style="text-decoration: underline; margin-left: 5px;">{l s="Ever." mod="latitude_official" }</span></p>

        <p style="font-weight: 600; font-size: 15px;">You will be redirected to the {$gateway_name} website when you select Place Order.</p>
    </div>
    {include file="$g_modal_path"}
{else if $currency_code === 'AUD'}
    {include file="module:latitude_official/views/templates/images_api/snippet.tpl"}
    {include file="module:latitude_official/views/templates/images_api/modal.tpl"}
{/if}