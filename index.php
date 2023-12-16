<?php
    if(!isset($_GET['Name']) || !isset($_GET['Oauth']))
    {
        ?>
        <html>
            <head>
                <title>Twitch Chat Bot</title>
                <!-- Latest compiled and minified CSS -->
                <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

                <!-- jQuery library -->
                <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

                <!-- Popper JS -->
                <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>

                <!-- Latest compiled JavaScript -->
                <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

                <meta name="viewport" content="width=device-width, initial-scale=1">
            </head>
            <body>
                <form action="asylumjen.php" method="get">
                    <div class="form-group">
                        <label for="Name">Twitch Name:</label>
                        <input type="text" class="form-control" placeholder="Twitch Name" id="Name" name="Name">
                    </div>
                    <div class="form-group">
                        <label for="Twitch OAuth">Password (<a href="https://twitchapps.com/tmi/">https://twitchapps.com/tmi/</a>):</label>
                        <input type="text" class="form-control" placeholder="Twitch OAuth" id="Oauth" name="Oauth">
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </body>
        </html>
        <?php
        die();
    }

    $Username = $_GET['Name'];
    $Oauth = $_GET['Oauth'];

    $Channel = $_GET['Channel'];

    // Read JSON file
    $json = file_get_contents('./conf-asylumjen.json');
    //Decode JSON
    $json_data = json_decode($json,true);

    $Prefix = $json_data['Prefix'];

    $Cheer = $json_data['Cheer'];

    $Commands = $json_data['Commands'];
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Twitch Chat Bot</title>
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

        <!-- jQuery library -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

        <!-- Popper JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>

        <!-- Latest compiled JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body style="text-align: center;">
        <div class="row">
            <div class="col-md-1"></div>
            <div class="col-md-4">
                <div style="padding-top: 40px;"></div>
                <h2>Twitch  Chat Bot</h2>
                <h3 id="BotState">State = Offline</h3>
                <hr style="border: 1px solid red;">
                <input type="text" id="MsgToSend"  style='width:99%;'>
                <div style="padding-top: 5px;"></div>
                <button id="SendMessage" type="button" class="btn btn-primary btn-block" onclick="sendTxtMessage()">Send</button>
                <hr style="border: 1px solid red;">
                <div class="row">
                    <div class="col-md">
                        <table style="width:90%" id="TwitchMods" class="table table-hover">
                        </table>
                    </div>
                    <div class="col-md"></div>
                </div>
                <hr>
                <h3>Commands</h3>
                <?php
                    foreach ($Commands as $Command) {
                        echo '<button type="button" class="btn btn-primary btn-block" onclick="sendMessage(\'' . $Command['Output'] . '\')">' . $Prefix . $Command['Command'] . '</button>';
                    }
                ?>
            </div>
            <div class="col-md-6">
                <div style="padding-top: 40px;"></div>
                <table style="width:90%" id="TwitchChat" class="table table-bordered table-hover">
                    <tr>
                        <th>Date</th>
                        <th>User</th> 
                        <th>Message</th>
                    </tr>
                </table>
            </div>
            <div class="col-md-1"></div>
        </div>
    </body>
</html>

<script src="tmi.min.js"></script>
<script src="linkify.js"></script>
<script src="linkify-string.js"></script>

<script>

    var LinkifyOptions = {/* … */};

    var Connected = "false";
    var Enabled = "false";

    // Define configuration options
    const opts = {
        identity: {
            username: "<?php echo $Username; ?>",
            password: "<?php echo $Oauth; ?>"
        },
        connection: {
            reconnect: true,
            secure: true
        },
        channels: [
            "asylumjen"
        ]
    };

    // Create a client with our options
    const client = new tmi.client(opts);

    client.on('message', onMessageHandler);
    client.on('mods', ModList);
    <?php if($Cheer)
     echo "client.on('cheer', OnCheerHandeler);";
    ?>
    client.on('connected', onConnectedHandler);

    // Connect to Twitch:
    client.connect();

    // Called every time the bot connects to Twitch chat
    function onConnectedHandler (addr, port) {
        console.log(`* Connected to Twitch on: ${addr}:${port}`);
        Connected = "true";
        document.getElementById("BotState").innerHTML = "State = Online";
        sendMessage("/mods");
    }

    // Called every time a message comes in
    function onMessageHandler (target, context, msg, self)
    {
        console.log(context);

        switch(context["message-type"])
        {
            case "action":
                // This is an action message..
                break;
            case "chat":
                // This is a chat message..
                ChatMessage(target, context, msg, self);
                break;
            case "whisper":
                // This is a whisper..
                break;
            default:
                // Something else ?
                break;
        }
    }

    function ChatMessage (target, context, msg, self)
    {
        // Remove whitespace from chat message
        const Message = msg.trim();

        if(context['mod'])
        {
            AddRow("TwitchChat", CurrentTime(), context['display-name'], Message, "warning");
        }
        else if (self)
        {
            AddRow("TwitchChat", CurrentTime(), context['display-name'], Message, "primary");
        }
        else
        {
            AddRow("TwitchChat", CurrentTime(), context['display-name'], Message, "info");
        }

        if (self) { return; } // Ignore messages from self

        // If the command is known, let's execute it
        <?php
            if($Username == "SamKemp55")
            {
                foreach ($Commands as $Command) {
                    // echo 'if (Message === "' . $Prefix . $Command['Command'] . '") {';
                    // echo 'sendMessage("' . $Command['Output'] . '".replace("%user%", context["display-name"]));';
                    // echo "}";
                }
            }
        ?>

        if (Message === '<?php echo $Prefix; ?>beep') {
            sendMessage("Boop");
        }
    }

    function OnCheerHandeler(channel, userstate, message)
    {
        //console.log(userstate);
        AddRow("TwitchChat", CurrentTime(), userstate['display-name'], message, "success");
        var Bits = userstate['bits'];
        if(Bits == 1)
        {
            sendMessage("Thank you " + userstate['display-name'] + " for the bit");
        }
        else
        {
            sendMessage("Thank you " + userstate['display-name'] + " for the " + Bits + " bits");
        }
    }

    function ModList(channel, mods)
    {
        //console.log(mods);
        for (var mod in mods)
        {
            AddMod("TwitchMods", mods[mod]);
        }
    }

    function AddMod(Table, ModName)
    {
        var table = document.getElementById(Table);
        var row = table.insertRow(0);
        var cell1 = row.insertCell(0);
        cell1.innerHTML = ModName;
    }

    function AddRow(Table, DTE, TwitchUser, MSG, STYL)
    {
        var table = document.getElementById(Table);
        var row = table.insertRow(1);
        var cell1 = row.insertCell(0);
        var cell2 = row.insertCell(1);
        var cell3 = row.insertCell(2);
        cell1.innerHTML = DTE;
        cell2.innerHTML = TwitchUser;
        cell3.innerHTML = linkifyStr(MSG, LinkifyOptions);
        row.classList.add("table-" + STYL);
    }

    function CurrentTime()
    {
        var today = new Date();
        return  today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
    }

    function sendTxtMessage()
    {
        sendMessage(document.getElementById("MsgToSend").value);
        document.getElementById("MsgToSend").value = "";
    }

    function sendMessage(msg)
    {
        if(Connected == "true")
        {
            client.say("asylumjen", msg);
        }
    }

    // Get the input field
    var input = document.getElementById("MsgToSend");

    // Execute a function when the user releases a key on the keyboard
    input.addEventListener("keyup", function(event) {
    // Number 13 is the "Enter" key on the keyboard
    if (event.keyCode === 13) {
        // Cancel the default action, if needed
        event.preventDefault();
        // Trigger the button element with a click
        document.getElementById("SendMessage").click();
    }
    });

</script>