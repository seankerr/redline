<h1>Components</h1>

<form:errors>
    <strong>Errors:</strong>
    <ul>
        [Error]<li>%param - %error</li>[/Error]
    </ul>
</form:errors>

<form:form id="form" method="post" enctype="multipart/form-data">
    <h3>&lt;form:text/&gt;</h3>
    <form:text id="text" x:default="default text"/>
    <h3>&lt;form:password/&gt;</h3>
    <form:password id="password"/>
    <h3>&lt;form:textarea/&gt;</h3>
    <form:textarea id="textarea" x:default="default text" x:pattern="#^abc#i"/>
    <h3>&lt;form:checkbox/&gt;</h3>
    <form:checkbox id="checkbox" x:on="t" x:off="f"/>
    <h3>&lt;form:select/&gt;</h3>
    <form:select id="select1">
        [Option #1, 1]
        [Option #2, 2]
        [Option #3, 3]
        [Option #4, 4]
        [Option #5, 5]
        [Option #6, 6]
    </form:select>
    <h3>&lt;form:select/&gt; with binding</h3>
    <form:select id="select2" x:bind="bind"/>
    <h3>&lt;form:multiselect/&gt;</h3>
    <form:multiselect id="selectmulti1" size="5">
        [Option #1, 1]
        [Option #2, 2]
        [Option #3, 3]
        [Option #4, 4]
        [Option #5, 5]
        [Option #6, 6]
    </form:multiselect>
    <h3>&lt;form:multiselect/&gt; with binding</h3>
    <form:multiselect id="selectmulti2" size="5" x:bind="bind"/>
    <h3>&lt;form:radio/&gt;</h3>
    <form:radio id="radio">
        <ul>
            <li>[1] Yes</li>
            <li>[2] No</li>
            <li>[3] Maybe</li>
        </ul>
    </form:radio>
    <h3>&lt;form:file/&gt;</h3>
    <form:file id="file" x:minsize="5"/>
    <h3>&lt;form:button/&gt;</h3>
    <form:button id="button" value="Button"/>
    <h3>&lt;form:submit/&gt;</h3>
    <form:submit id="submit" value="Submit"/>
    <h3>&lt;form:reset/&gt;</h3>
    <form:reset id="reset" value="Reset"/>
</form:form>
