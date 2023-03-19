<h1 align="center">
    <img src="https://media3.giphy.com/media/ZcdZ7ldgeIhfesqA6E/giphy.gif?cid=ecf05e47aawgrwx2bbpyn75jxq121vn8hosk04n1n786d7p0&rid=giphy.gif&ct=s" width="5%"></img><b> </b>Telegram invite bot
</h1>

<p align="center">
    <img src="https://img.shields.io/badge/technologies-php%2C%20telegram%20bot%20api-orange">
    <img src="https://img.shields.io/badge/creator-seyoulax-brightgreen">
    <img src="https://img.shields.io/github/repo-size/seyoulax/invite-bot">
</p>

### Quick description:

 <p> - This is the bot that can help you to <ins>invite people to private groups in telegram and organize some info stuff there</ins> )</p>
 
### Who might need this project:

<ul>
    <li>Organizations (to assemle employees in certain place)</li>
    <li>For people with similar hobbies (cultivate something together)</li>
    <li>Many others people</li>
</ul>
 
### Some things which can help you to read code:

<ul> 
<li>Whole project use russian language but comments are in English</li>
<li>You need to set up telegram webhook for using this code</li>
<li>This project doesn`t import any libraries to work with telegram api and all methods are handwritten</li>
<li>It`s my first serious project so dont judge me a lot)</li>
<li>Im only beginner in English so some comments in code might seem weird for you</li>
<li>This project was developed for company so I`d to hide some secret information in code (links and company mention).</li>
<li>
<details>
    <summary><b>I left comments and tips</b></summary>
       
```php 
# 2 question (user`s name)
$textToSend = "(text to let user know that he was banned)";
```

</details>
</li>
</ul>

### Example of using code
<p>
    
> **Questions in poll**
>
> <img src="https://im4.ezgif.com/tmp/ezgif-4-42b41271fe.gif">
    
> **Approvement by admin**
>    
> <img src="https://im.ezgif.com/tmp/ezgif-1-e44550c411.gif">
    
> **Closest event button (example of button in chat)**
>    
> <img src="https://im.ezgif.com/tmp/ezgif-1-47d63fe18b.gif">
    
</p>


### Database structure

| TABLE NAME | FIELDS | FEATURES |
|----------------|:---------:|----------------:|
| chat_users | similar to middle users but we add `is_searching` and `status` | this table involves users which are now in group or just left |
| middle_users | `telegram_id` and fields related to questions like `name` or `phone number`  | this table involves users which are passing poll at the moment  |
| users | some users info from telegram like `telegram_id` or `username` and current poll status(to know what question we need to send user now ) |this table consists of users which pressed `/start`| 
| events(optional)| event info and field `is_active` | here we gather up events and point active one | 

_______

# Thank you for reading!!!
