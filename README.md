# What is WorkingOn-CLI

WorkingOn-CLI is a *very* simple time tracking tool. I needed to start tracking
time against certain projects for work and instead of getting an overblown tool
I decided to build my own.

The features are very sparse here as I didn't need much other than something to
track the start and end time of my project. The most advanced feature is the 
ability to print out report of time spent. I don't allow for tracking by project
only time tracking by task. I don't even add times together if you have the
exact same task in there twice.

## How To

Because it's a very simple tool, there aren't a whole lot of options to forget
while using WorkingOn-CLI.

### Start a task

workingon start "Project name: This is the task I'm starting"

### End a task

workingon end

### Print out current task

workingon current

### Print a report 

workingon report
  Currently prints a report based on ALL dates. Limiting by date will be in
  the next version.

That's it, nothing more.

## Setting up WorkingOn-CLI

So this is how I have it setup, you can set it up a different way if you choose.

Download (or clone the repo). From the directory you will need to link the file
a place in your path (/usr/local/bin should work)
`ln -s /path/to/WorkingOn-CLI/workingon.php /usr/local/bin/workingon`

That's it. The script will keep the flat files in your home directory under a
directory called .workingon. If you remove these files, you will have no reports
for that time frame.

## Extending

The code is straight PHP. No classes at all currently (remember this was built
quickly to match what I needed). If you would like to extend this or build a
feature, feel free.

## Helpful reminder

I am using this mostly on my *buntu Desktop and found that it's really easy to
forget to change what you are currently working on. I have a cron that runs
every 20 minutes to remind me of what I'm currently working on and it prompts me
to change my status.

    #!/bin/sh

    CURRENT=`/usr/local/bin/workingon current`

    /usr/bin/notify-send "What are you WorkingOn?" \
     "Check your time.\n $CURRENT" \
     -i /usr/share/pixmaps/gnome-set-time.png

My cron for running this is as follows:

    */20 7-17 * * 1-5 DISPLAY=:0.0 /usr/local/bin/reminder.sh
