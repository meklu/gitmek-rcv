<?php
#### GH PAYLOAD
$gh_ex_payload = <<< 'EOF'
{
   "after":"1481a2de7b2a7d02428ad93446ab166be7793fbb",
   "before":"17c497ccc7cca9c2f735aa07e9e3813060ce9a6a",
   "commits":[
      {
         "added":[
         ],
         "author":{
            "email":"lolwut@noway.biz",
            "name":"Garen Torikian",
            "username":"octokitty"
         },
         "committer":{
            "email":"lolwut@noway.biz",
            "name":"Garen Torikian",
            "username":"octokitty"
         },
         "distinct":true,
         "id":"c441029cf673f84c8b7db52d0a5944ee5c52ff89",
         "message":"Test",
         "modified":[
            "README.md"
         ],
         "removed":[
         ],
         "timestamp":"2013-02-22T13:50:07-08:00",
         "url":"https://github.com/octokitty/testing/commit/c441029cf673f84c8b7db52d0a5944ee5c52ff89"
      },
      {
         "added":[
         ],
         "author":{
            "email":"lolwut@noway.biz",
            "name":"Garen Torikian",
            "username":"octokitty"
         },
         "committer":{
            "email":"lolwut@noway.biz",
            "name":"Garen Torikian",
            "username":"octokitty"
         },
         "distinct":true,
         "id":"36c5f2243ed24de58284a96f2a643bed8c028658",
         "message":"This is me testing the windows client.",
         "modified":[
            "README.md"
         ],
         "removed":[
         ],
         "timestamp":"2013-02-22T14:07:13-08:00",
         "url":"https://github.com/octokitty/testing/commit/36c5f2243ed24de58284a96f2a643bed8c028658"
      },
      {
         "added":[
            "words/madame-bovary.txt"
         ],
         "author":{
            "email":"lolwut@noway.biz",
            "name":"Garen Torikian",
            "username":"octokitty"
         },
         "committer":{
            "email":"lolwut@noway.biz",
            "name":"Garen Torikian",
            "username":"octokitty"
         },
         "distinct":true,
         "id":"1481a2de7b2a7d02428ad93446ab166be7793fbb",
         "message":"Rename madame-bovary.txt to words/madame-bovary.txt",
         "modified":[
         ],
         "removed":[
            "madame-bovary.txt"
         ],
         "timestamp":"2013-03-12T08:14:29-07:00",
         "url":"https://github.com/octokitty/testing/commit/1481a2de7b2a7d02428ad93446ab166be7793fbb"
      }
   ],
   "compare":"https://github.com/octokitty/testing/compare/17c497ccc7cc...1481a2de7b2a",
   "created":false,
   "deleted":false,
   "forced":false,
   "head_commit":{
      "added":[
         "words/madame-bovary.txt"
      ],
      "author":{
         "email":"lolwut@noway.biz",
         "name":"Garen Torikian",
         "username":"octokitty"
      },
      "committer":{
         "email":"lolwut@noway.biz",
         "name":"Garen Torikian",
         "username":"octokitty"
      },
      "distinct":true,
      "id":"1481a2de7b2a7d02428ad93446ab166be7793fbb",
      "message":"Rename madame-bovary.txt to words/madame-bovary.txt",
      "modified":[
      ],
      "removed":[
         "madame-bovary.txt"
      ],
      "timestamp":"2013-03-12T08:14:29-07:00",
      "url":"https://github.com/octokitty/testing/commit/1481a2de7b2a7d02428ad93446ab166be7793fbb"
   },
   "pusher":{
      "email":"lolwut@noway.biz",
      "name":"Garen Torikian"
   },
   "ref":"refs/heads/master",
   "repository":{
      "created_at":1332977768,
      "description":"",
      "fork":false,
      "forks":0,
      "has_downloads":true,
      "has_issues":true,
      "has_wiki":true,
      "homepage":"",
      "id":3860742,
      "language":"Ruby",
      "master_branch":"master",
      "name":"testing",
      "open_issues":2,
      "owner":{
         "email":"lolwut@noway.biz",
         "name":"octokitty"
      },
      "private":false,
      "pushed_at":1363295520,
      "size":2156,
      "stargazers":1,
      "url":"https://github.com/octokitty/testing",
      "watchers":1
   }
}
EOF;


#### BB PAYLOAD
$bb_ex_payload = <<< 'EOF'
{
    "canon_url": "https://bitbucket.org",
    "commits": [
        {
            "author": "marcus",
            "branch": "master",
            "files": [
                {
                    "file": "somefile.py",
                    "type": "modified"
                },
                {
                    "file": "derpbun.cs",
                    "type": "added"
                },
                {
                    "file": "dudebro.rb",
                    "type": "removed"
                }
            ],
            "message": "Added some more things to somefile.pyn",
            "node": "620ade18607a",
            "parents": [
                "702c70160afc"
            ],
            "raw_author": "Marcus Bertrand <marcus@somedomain.com>",
            "raw_node": "620ade18607ac42d872b568bb92acaa9a28620e9",
            "revision": null,
            "size": -1,
            "timestamp": "2012-05-30 05:58:56",
            "utctimestamp": "2012-05-30 03:58:56+00:00"
        }
    ],
    "repository": {
        "absolute_url": "/marcus/project-x/",
        "fork": false,
        "is_private": true,
        "name": "Project X",
        "owner": "marcus",
        "scm": "git",
        "slug": "project-x",
        "website": "https://atlassian.com/"
    },
    "user": "marcus"
}
EOF;
?>
