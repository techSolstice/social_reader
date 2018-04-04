Social Reader is a quick project showing the basics of Symfony 4 and Doctrine as I learn them.  It uses the Twitter and Twitch APIs to give you a limited feed.

![ScreenShot](screenshots/social_main.png)

The Twitch feed will currently show the top streams.
![ScreenShot](screenshots/social_twitch.png)

The Twitter feed will default to a search of Vegas tweets:
![ScreenShot](screenshots/social_twitter_default.png)

or you can change the URL to have it search for tweets based on a different term.
![ScreenShot](screenshots/social_twitter_vehicles.png)


You will need to customize the .env file and modify your Symfony, database, and API configuration variables