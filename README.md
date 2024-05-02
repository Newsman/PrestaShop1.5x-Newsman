# NewsMAN Plugin for PrestaShop 1.5x 

![](https://raw.githubusercontent.com/Newsman/PrestaShop1.5x-Newsman/master/assets/newsmanBr.jpg)

# PrestaShop-NewsMAN

Introducing the NewsMAN plugin for PrestaShop. Sync your PrestaShop customers/subscribers to the [NewsMAN](https://www.newsmanapp.com) list/segments. This is the easiest way to connect your shop with NewsMAN. Generate an API KEY in your NewsMAN account, install this plugin, and you will quickly sync your shop customers and newsletter subscribers with NewsMAN list/segments.

#Installation

Manual installation: Copy the "NewsMAN" directory from this repository to your "modules" shop directory. Copy "NewsMANfetch.php" to your root directory.

#Setup
1. Enter your NewsMAN API KEY and User ID, then click Connect
![](https://raw.githubusercontent.com/Newsman/PrestaShop1.5x-Newsman/master/assets/api-setup-screen.png)

2. Choose destination segments for your newsletter subscribers and customer groups. All your groups will be listed and you can select the NewsMAN Segment  associate with. Additionally, you have the option to ignore the group or upload its members while incorporating them into any segment. Ensure that these segments are configured in your NewsMAN account for them to appear in this form.
![](https://raw.githubusercontent.com/Newsman/PrestaShop1.5x-Newsman/master/assets/mapping-screen.png)

3. Choose how often you want your lists to get uploaded to Newsman
You can also do a manual synchronization by clicking "Synchronize now".
![](https://raw.githubusercontent.com/Newsman/PrestaShop1.5x-Newsman/master/assets/sync-screen.png)

# Sync Segmentation

Newsletter subscribers: email, newsletter_date_add, source
Customer groups: email, firstname, lastname, gender, source

For automatic synchronization to function, it is essential to have the "native" "Cron tasks manager" (cronjobs) module installed and properly configured.

Description

Subscription Forms & Pop-ups

Create visually appealing forms and pop-ups to captivate potential leads through embedded newsletter signups or exit-intent popups.
Ensure consistency across devices, providing a seamless user experience.
Integrate forms with automations for swift responses and the delivery of welcoming emails.

Contact Lists & Segments

Efficiently import and synchronize contact lists from various sources to streamline data management.
Apply segmentation techniques to precisely target audience segments based on demographics or behavior.

Email & SMS Marketing Campaigns

Effortlessly dispatch mass campaigns, newsletters, or promotions to a broad subscriber base.
Tailor campaigns for individual subscribers by incorporating their names and suggesting pertinent products.
Re-engage subscribers by reissuing campaigns to those who haven't opened the initial email.

Email & SMS Marketing Automation

Automate personalized product recommendations, follow-up emails, and strategies for addressing cart abandonment.
Strategically tackle cart abandonment or highlight related products to encourage finalized purchases.
Collect post-purchase feedback to gauge customer satisfaction.

Ecommerce Remarketing

Reconnect with subscribers through targeted offers derived from past interactions.
Personalize interactions with exclusive offers or reminders based on user behavior or preferences.

SMTP Transactional Emails

Ensure the punctual and reliable delivery of crucial messages, such as order confirmations or shipping notifications, through SMTP.

Extended Email and SMS Statistics

Gain comprehensive insights into open rates, click-through rates, conversion rates, and overall campaign performance for well-informed decision-making.
The NewsMAN Plugin simplifies your marketing endeavors without hassle, facilitating seamless communication with your audience.



