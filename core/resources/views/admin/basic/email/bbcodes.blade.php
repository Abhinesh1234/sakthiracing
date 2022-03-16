<table class="table table-striped mb-5 bbcodes-table" style="border: 1px solid #0000005a;">
    <thead>
      <tr>
        <th scope="col">Short Code</th>
        <th scope="col">Meaning</th>
      </tr>
    </thead>
    <tbody>

        @if ($templateInfo->email_type == 'email_verification')
          <tr>
              <td>
                {customer_name}
              </td>
              <th scope="row">
                Customer Name
              </th>
          </tr>
          <tr>
              <td>
                {verification_link}
              </td>
              <th scope="row">
                Email verification link
              </th>
          </tr>
        @endif

        @if ($templateInfo->email_type != 'email_verification')
        <tr>
            <td>
              {username}
            </td>
            <th scope="row">
              Username
            </th>
        </tr>
        @endif

        @if ($templateInfo->email_type == 'custom_domain_connected' || $templateInfo->email_type == 'custom_domain_rejected')
        <tr>
            <td>
              {requested_domain}
            </td>
            <th scope="row">
              Requested Custom Domain of User
            </th>
        </tr>
        @endif
        @if ($templateInfo->email_type == 'custom_domain_connected')
        <tr>
            <td>
              {previous_domain}
            </td>
            <th scope="row">
              Previous Custom Domain of User
            </th>
        </tr>
        @endif
        @if ($templateInfo->email_type == 'custom_domain_rejected')
        <tr>
            <td>
              {current_domain}
            </td>
            <th scope="row">
              Current Custom Domain of User
            </th>
        </tr>
        @endif
        @if ($templateInfo->email_type == 'registration_with_premium_package' || $templateInfo->email_type == 'registration_with_trial_package' || $templateInfo->email_type == 'registration_with_free_package' || $templateInfo->email_type == 'membership_extend' || $templateInfo->email_type == 'payment_accepted_for_membership_extension_offline_gateway' || $templateInfo->email_type == 'payment_accepted_for_registration_offline_gateway')


        <tr>
            <td>
              {package_title}
            </td>
            <th scope="row">
              Package Name
            </th>
        </tr>
        <tr>
            <td>
              {package_price}
            </td>
            <th scope="row">
              Price of Purchased Package
            </th>
        </tr>
        <tr>
            <td>
              {activation_date}
            </td>
            <th scope="row">
              Start Date of Membership
            </th>
        </tr>
        <tr>
            <td>
              {expire_date}
            </td>
            <th scope="row">
              Expire Date of Membership
            </th>
        </tr>
        @endif
        @if ($templateInfo->email_type == 'membership_expiry_reminder')
        <tr>
            <td>
              {last_day_of_membership}
            </td>
            <th scope="row">
              Last day of membership
            </th>
        </tr>
        @endif
        @if ($templateInfo->email_type == 'membership_expiry_reminder' || $templateInfo->email_type == 'membership_expired')
        <tr>
            <td>
              {login_link}
            </td>
            <th scope="row">
              Login Page Link for User
            </th>
        </tr>
        @endif


        <tr>
          <td>
            {website_title}
          </td>
          <th scope="row">
            Website Title
          </th>
        </tr>

    </tbody>
</table>
